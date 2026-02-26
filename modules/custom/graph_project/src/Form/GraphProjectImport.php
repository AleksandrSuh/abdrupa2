<?php

namespace Drupal\graph_project\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use PhpOffice\PhpWord\IOFactory;
use Drupal\node\Entity\Node;

/**
 * Provides a form for importing budget schedule from DOCX.
 */
class GraphProjectImport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graph_project_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['enctype'] = 'multipart/form-data';

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>Загрузите файл в формате DOC с графиком подготовки проекта бюджета. Файл должен содержать таблицу с колонками: Наименование мероприятия, Срок выполнения, Ответственный.</p>'),
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Файл с графиком'),
      '#description' => $this->t('Выберите файл .docx'),
      '#upload_location' => 'public://graph_project/',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'doc docx',
        ],
      ],
      '#required' => TRUE,
    ];

    $form['budget_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Год бюджета'),
      '#description' => $this->t('Основной год, на который разрабатывается бюджет'),
      '#min' => 2020,
      '#max' => 2060,
      '#required' => TRUE,
    ];

    $form['plan_years'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Плановый период'),
      '#description' => $this->t('Например: 2026-2028'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Импортировать график'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $files = $form_state->getValue('file');
    if (empty($files)) {
      return;
    }

    // Сохраняем файл постоянно
    $file = File::load($files[0]);
    $file->setPermanent();
    $file->save();

    $file_path = $file->getFileUri();
    $budget_year = $form_state->getValue('budget_year');
    $plan_years = $form_state->getValue('plan_years');

    try {
      $imported_count = $this->processFile($file_path, $budget_year, $plan_years);

      // Логируем успешный импорт
      \Drupal::logger('graph_project')->info('Импорт графика за @year: @count мероприятий из файла @filename', [
        '@year' => $budget_year,
        '@count' => $imported_count,
        '@filename' => $file->getFilename(),
      ]);

      // Сохраняем информацию о последнем импорте в конфигурацию
      \Drupal::configFactory()->getEditable('graph_project.settings')
        ->set('last_import', [
          'date' => \Drupal::time()->getRequestTime(),
          'year' => $budget_year,
          'plan_years' => $plan_years,
          'count' => $imported_count,
          'filename' => $file->getFilename(),
          'uid' => \Drupal::currentUser()->id(),
        ])
        ->save();

      $this->messenger()->addMessage($this->t('Импорт завершен. Добавлено/обновлено @count мероприятий.', [
        '@count' => $imported_count,
      ]));

    } catch (\Exception $e) {
      // Логируем ошибку
      \Drupal::logger('graph_project')->error('Ошибка импорта: @error', [
        '@error' => $e->getMessage(),
      ]);

      $this->messenger()->addError($this->t('Ошибка при обработке файла: @error', [
        '@error' => $e->getMessage(),
      ]));
    }
  }

  /**
   * Process the uploaded file and import data.
   */
  private function processFile($file_path, $budget_year, $plan_years) {

    $real_path = \Drupal::service('file_system')->realpath($file_path);

    try {
      // Включаем все ошибки PHPWord
      $old = error_reporting(E_ALL);
      $phpWord = IOFactory::load($real_path);
      // Проходим по всем секциям документа
      error_reporting($old);

      // ОЧИЩАЕМ СУЩЕСТВУЮЩИЕ ДАННЫЕ ЗА ЭТОТ ГОД
      $deleted_count = $this->deleteExistingItems($budget_year);

      $this->messenger()->addMessage($this->t('Удалено @count существующих мероприятий за @year год', [
        '@count' => $deleted_count,
        '@year' => $budget_year,
      ]));


      //$this->messenger()->addMessage('Документ загружен, секций: ' . count($phpWord->getSections()));

      $imported_count = 0;
      foreach ($phpWord->getSections() as $section) {
        // Ищем таблицы в секции
        foreach ($section->getElements() as $element) {
          if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            $table_items = $this->parseTable($element, $budget_year, $plan_years);
            $imported_count += $this->saveItems($table_items);
          }
        }
      }

      return $imported_count;

    } catch (\Exception $e) {
      // Ловим исключение PHPWord
      $this->messenger()->addError('Ошибка PHPWord: ' . $e->getMessage());
      \Drupal::logger('graph_project')->error('PHPWord error: @error', ['@error' => $e->getTraceAsString()]);
      throw $e;
    }


  }

  private function deleteExistingItems($budget_year) {
    $count = 0;

    try {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');

      // Находим все материалы за указанный год
      $existing_nodes = $node_storage->loadByProperties([
        'type' => 'graph_item',
        'field_year' => $budget_year,
      ]);

      $count = count($existing_nodes);

      if ($count > 0) {
        // Удаляем найденные материалы
        $node_storage->delete($existing_nodes);

        \Drupal::logger('graph_project')->info('Удалено @count старых записей за @year год', [
          '@count' => $count,
          '@year' => $budget_year,
        ]);
      }

    } catch (\Exception $e) {
      \Drupal::logger('graph_project')->error('Ошибка при удалении старых записей: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $count;
  }



  private function parseTable($table, $budget_year, $plan_years) {
    $items = [];
    $rows = $table->getRows();

    // Пропускаем заголовки (обычно первые 2 строки)
    // В вашей таблице: "Наименование мероприятия | Срок | Ответственный" и "1 | 2 | 3"
    $start_row = 2;
    \Drupal::logger('graph_project')->debug('Всего строк в таблице: @count', ['@count' => count($rows)]);

    foreach ($rows as $row_index => $row) {
      \Drupal::logger('graph_project')->debug('Обрабатываем строку @index', ['@index' => $row_index]);

      if ($row_index < $start_row) continue;

      // FIX FOR V1.4: Используем getCells() вместо getElements()
      $cells = $row->getCells();
      if (count($cells) < 3) continue;

      // Получаем текст из ячеек
      $title_cell = $this->getCellTextV14($cells[0]);
      $deadline_cell = $this->getCellTextV14($cells[1]);
      $responsible_cell = $this->getCellTextV14($cells[2]);


      if ($row_index < 5) {
        $elements = $cells[0]->getElements();
        $part_count = count($elements);
        \Drupal::logger('graph_project')->debug('Ячейка строки @index содержит @count элементов', [
          '@index' => $row_index,
          '@count' => $part_count,
        ]);

        foreach ($elements as $idx => $element) {
          $element_text = '';
          if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $element_text = $element->getText();
          } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $te) {
              if ($te instanceof \PhpOffice\PhpWord\Element\Text) {
                $element_text .= $te->getText();
              }
            }
          }
          \Drupal::logger('graph_project')->debug('  Элемент @idx: "@text"', [
            '@idx' => $idx,
            '@text' => $element_text,
          ]);
        }
      }


      // Пропускаем пустые строки
      if (empty(trim($title_cell))) continue;

      // Извлекаем номер пункта из начала строки
      $item_number = $this->extractItemNumber($title_cell);
      $item_text = $this->extractItemText($title_cell);
      $short_title = mb_substr($title_cell, 0, 100) . (mb_strlen($title_cell) > 100 ? '…' : '');

      // Определяем уровень по количеству точек в номере
      $level = substr_count($item_number, '.');

      // Определяем родительский номер (обрезаем последний сегмент)
      $parent_number = $this->getParentNumber($item_number);

      // Получаем классы строки (если есть в HTML, но в PHPWord их может не быть)
      // Это для future reference - в DOCX классах может не быть
      $row_classes = '';

      $items[] = [
        'number' => $item_number,
        'title' => $short_title,
        'full_title' => $title_cell, // сохраняем оригинал
        'deadline_raw' => $deadline_cell,
        'deadline' => $this->parseDate($deadline_cell),
        'responsible' => $responsible_cell,
        'level' => $level,
        'parent_number' => $parent_number,
        'budget_year' => $budget_year,
        'plan_years' => $plan_years,
        'classes' => $row_classes,
        'row_index' => $row_index,
      ];
    }

    return $items;
  }

  /**
   * Extract item number from the beginning of string.
   * Examples: "1. Текст" -> "1", "3.1 Текст" -> "3.1"
   */
  /**
   * Extract item number from the beginning of string.
   * Examples: "1. Текст" -> "1", "3.1 Текст" -> "3.1"
   */
  private function extractItemNumber($text) {
    // Очищаем текст от невидимых символов
    $text = preg_replace('/\x{00A0}/u', ' ', $text);
    $text = trim($text);

    \Drupal::logger('graph_project')->debug('extractItemNumber raw text: "@text"', ['@text' => $text]);

    // Паттерн 1: номер с точкой в начале строки (1. или 3.1.)
    if (preg_match('/^(\d+(?:\.\d+)*)\./u', $text, $matches)) {
      \Drupal::logger('graph_project')->debug('Pattern 1 matched: "@match"', ['@match' => $matches[1]]);
      return $matches[1];
    }

    // Паттерн 2: номер с пробелом в начале строки (1 Текст)
    if (preg_match('/^(\d+(?:\.\d+)*)\s/u', $text, $matches)) {
      \Drupal::logger('graph_project')->debug('Pattern 2 matched: "@match"', ['@match' => $matches[1]]);
      return $matches[1];
    }

    // Паттерн 3: проверяем, не начинается ли текст с цифры, даже без точки
    if (preg_match('/^(\d+)/u', $text, $matches)) {
      \Drupal::logger('graph_project')->debug('Pattern 3 (digit start) matched: "@match"', ['@match' => $matches[1]]);
      return $matches[1];
    }

    \Drupal::logger('graph_project')->debug('No number found at beginning of text');
    return '';
  }


  private function extractItemText($text) {

    if (preg_match('/^(\d+(?:\.\d+)*)[\.\s]*(.*)$/u', $text, $matches)) {
      return trim($matches[2]);     }

    // Если номер не найден, возвращаем весь текст
    return $text;
  }


  private function getParentNumber($number) {
    if (empty($number)) return '';

    $parts = explode('.', $number);
    array_pop($parts);

    return implode('.', $parts);
  }

  /**
   * Parse date from various formats.
   */
  private function parseDate($date_string) {
    $date_string = trim($date_string);

    // Если прочерк или пусто
    if ($date_string === '-' || empty($date_string)) {
      return NULL;
    }

    // Пробуем распарсить дату в формате ДД.ММ.ГГГГ
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $date_string, $matches)) {
      return $matches[3] . '-' . $matches[2] . '-' . $matches[1]; // YYYY-MM-DD
    }

    // Если не получилось, возвращаем NULL
    return NULL;
  }


  private function getCellTextV14($cell) {
    if (!$cell) return '';

    $text_parts = [];

    $elements = $cell->getElements();

    foreach ($elements as $element) {
      $part = '';

      if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
        $part = $element->getText();
      } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        foreach ($element->getElements() as $textElement) {
          if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
            $part .= $textElement->getText();
          }
        }
      } elseif (method_exists($element, 'getText')) {
        $part = $element->getText();
      }

      if (!empty($part)) {
        $text_parts[] = $part;
      }
    }

    // Соединяем части через пробел
    return trim(implode(' ', $text_parts));
  }


  private function getCellTextV14__($cell) { // FIX: новый метод для версии 1.4
    if (!$cell) return '';

    $text = '';

    // В PHPWord 1.4 у ячейки есть метод getElements() - это массив!
    $elements = $cell->getElements();

    foreach ($elements as $element) {
      if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
        $text .= $element->getText();
      } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        foreach ($element->getElements() as $textElement) {
          if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
            $text .= $textElement->getText();
          }
        }
      } elseif ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
        $text .= $element->getText();
      }
      // Добавим обработку других возможных типов
      elseif (method_exists($element, 'getText')) {
        $text .= $element->getText();
      }
    }

    return trim($text);
  }

  /**
   * Save parsed items as Drupal nodes.
   */
  private function saveItems($items) {
    $count = 0;
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_map = [];

    // Просто создаём все элементы (старых уже нет)
    foreach ($items as $item) {
      try {
        $node = $node_storage->create([
          'type' => 'graph_item',
          'title' => $item['title'],
          'uid' => \Drupal::currentUser()->id(),
        ]);

        // Заполняем поля
        $node->set('field_item_number', $item['number']);
        $node->set('field_item_text', $item['full_title']);
        $node->set('field_deadline_raw', $item['deadline_raw']);
        $node->set('field_sort', $item['row_index']); // порядковый номер

        if ($item['deadline']) {
          $node->set('field_deadline', $item['deadline']);
        }

        $node->set('field_responsible', $item['responsible']);
        $node->set('field_level', $item['level']);
        $node->set('field_year', $item['budget_year']);
        $node->set('field_plan_years', $item['plan_years']);

        if (!empty($item['classes'])) {
          $node->set('field_klassy_stili', $item['classes']);
        }

        $node->save();

        $node_map[$item['number']] = $node->id();
        $count++;

      } catch (\Exception $e) {
        \Drupal::logger('graph_project')->error('Error saving item @number: @error', [
          '@number' => $item['number'],
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Второй проход: устанавливаем родительские связи
    foreach ($items as $item) {
      if (!empty($item['parent_number']) && isset($node_map[$item['number']])) {
        $parent_id = $node_map[$item['parent_number']] ?? NULL;

        if ($parent_id) {
          $node = $node_storage->load($node_map[$item['number']]);
          $node->set('field_parent_item', ['target_id' => $parent_id]);
          $node->save();
        }
      }
    }

    return $count;
  }

  /**
   * Debug method to examine cell structure
   */
  private function debugCell($cell, $row_index, $cell_index) {
    if (!$cell) {
      \Drupal::logger('graph_project')->debug('Cell is null');
      return;
    }

    // Смотрим, какие методы доступны у объекта cell
    $methods = get_class_methods($cell);
    //\Drupal::logger('graph_project')->debug('Cell methods: @methods', ['@methods' => implode(', ', $methods)]);

    // Пробуем разные способы получить содержимое
    if (method_exists($cell, 'getContent')) {
      $content = $cell->getContent();
      \Drupal::logger('graph_project')->debug('getContent() type: @type', ['@type' => gettype($content)]);
      if (is_array($content)) {
        \Drupal::logger('graph_project')->debug('getContent() count: @count', ['@count' => count($content)]);
      } elseif (is_object($content)) {
        \Drupal::logger('graph_project')->debug('getContent() class: @class', ['@class' => get_class($content)]);
      }
    }

    if (method_exists($cell, 'getElements')) {
      $elements = $cell->getElements();
      //\Drupal::logger('graph_project')->debug('getElements() type: @type', ['@type' => gettype($elements)]);
    }

    if (method_exists($cell, 'getText')) {
      $text = $cell->getText();
      //\Drupal::logger('graph_project')->debug('getText(): @text', ['@text' => $text]);
    }
  }

}
