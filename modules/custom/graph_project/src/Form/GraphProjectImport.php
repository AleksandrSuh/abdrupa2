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
          'fid' => $file->id(), // сохраняем ID файла
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

    //\Drupal::logger('graph_project')->debug('Всего строк в таблице: @count', ['@count' => count($rows)]);

    // Массив для отслеживания текущих номеров на каждом уровне
    $counters = [0]; // уровень 0 (корень)

    // Пропускаем заголовки (первые строки)
    $start_row = 1;

    foreach ($rows as $row_index => $row) {
      if ($row_index < $start_row) continue;

      $cells = $row->getCells();
      if (count($cells) < 3) continue;

      if (method_exists($cells[0], 'getStyle')) {
        $cellStyle = $cells[0]->getStyle();
        /*if ($cellStyle) {
          \Drupal::logger('graph_project')->debug('Cell style class: @class', [
            '@class' => get_class($cellStyle)
          ]);
        }*/
      }

      // Получаем текст из ячеек
      $title_cell = $this->getCellTextV14($cells[0]);
      $deadline_cell = $this->getCellTextV14($cells[1]);
      $responsible_cell = $this->getCellTextV14($cells[2]);

      if (empty(trim($title_cell))) continue;

      // ОПРЕДЕЛЯЕМ УРОВЕНЬ ПО СТИЛЮ ИЛИ ОТСТУПУ
      // В PHPWord можно получить стиль абзаца
      $level = $this->detectLevel($row, $cells[0]);

      // Обновляем счётчики для этого уровня
      $counters = $this->updateCounters($counters, $level);

      // Генерируем номер
      $item_number = $this->generateNumber($counters, $level);

      // Создаём краткий заголовок
      $short_title = mb_substr($title_cell, 0, 100);
      if (mb_strlen($title_cell) > 100) {
        $short_title .= '…';
      }

      // Определяем родительский номер
      $parent_number = $this->getParentNumber($item_number);

      $items[] = [
        'number' => $item_number,
        'full_title' => $title_cell,
        'title' => $short_title,
        'deadline_raw' => $deadline_cell,
        'deadline' => $this->parseDate($deadline_cell),
        'responsible' => $responsible_cell,
        'level' => $level,
        'parent_number' => $parent_number,
        'budget_year' => $budget_year,
        'plan_years' => $plan_years,
        'classes' => '',
        'row_index' => $row_index,
      ];

      /*\Drupal::logger('graph_project')->debug('Строка @idx: номер="@num", уровень=@level, текст="@text"', [
        '@idx' => $row_index,
        '@num' => $item_number,
        '@level' => $level,
        '@text' => $short_title,
      ]);*/
    }

    return $items;
  }

  /**
   * Определяет уровень вложенности строки
   */
  private function detectLevel($row, $cell) {
    try {
      $cellElements = $cell->getElements();

      foreach ($cellElements as $cellElement) {
        if ($cellElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
          $paragraphStyle = $cellElement->getParagraphStyle();

          if ($paragraphStyle instanceof \PhpOffice\PhpWord\Style\Paragraph) {
            $indentation = $paragraphStyle->getIndentation();

            if ($indentation) {
              $leftIndent = $indentation->getLeft();

              //\Drupal::logger('graph_project')->debug('detectLevel: left=@left', ['@left' => $leftIndent]);

              // Основные пункты: отрицательный отступ (-57)
              if ($leftIndent < 0) {
                return 0;
              }
              // Подпункты первого уровня: положительный отступ (227)
              elseif ($leftIndent > 0 && $leftIndent < 500) {
                return 1;
              }
              // Более глубокие подпункты (если будут)
              elseif ($leftIndent >= 500) {
                return 2;
              }
            }
          }
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('graph_project')->error('detectLevel error: @error', ['@error' => $e->getMessage()]);
    }

    return 0;
  }

  /**
   * Обновляет счётчики для генерации номеров
   */
  private function updateCounters($counters, $level) {
    // Увеличиваем счётчик на текущем уровне
    if (isset($counters[$level])) {
      $counters[$level]++;
    } else {
      $counters[$level] = 1;
    }

    // Сбрасываем все счётчики на более глубоких уровнях
    // Например: перешли с 0 на 1 - сбрасываем 1 и выше
    // Перешли с 1 на 0 - сбрасываем 1 и выше (все глубже текущего)
    for ($i = $level + 1; $i < count($counters); $i++) {
      unset($counters[$i]);
    }

    // Убеждаемся, что нет "дырок" в индексах
    $counters = array_values($counters);

    return $counters;
  }

  /**
   * Генерирует номер из счётчиков
   */
  private function generateNumber($counters, $level) {
    $parts = [];
    for ($i = 0; $i <= $level; $i++) {
      if (isset($counters[$i])) {
        $parts[] = $counters[$i];
      }
    }
    return implode('.', $parts);
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

    //\Drupal::logger('graph_project')->debug('extractItemNumber raw text: "@text"', ['@text' => $text]);

    // Паттерн 1: номер с точкой в начале строки (1. или 3.1.)
    if (preg_match('/^(\d+(?:\.\d+)*)\./u', $text, $matches)) {
      //\Drupal::logger('graph_project')->debug('Pattern 1 matched: "@match"', ['@match' => $matches[1]]);
      return $matches[1];
    }

    // Паттерн 2: номер с пробелом в начале строки (1 Текст)
    if (preg_match('/^(\d+(?:\.\d+)*)\s/u', $text, $matches)) {
      //\Drupal::logger('graph_project')->debug('Pattern 2 matched: "@match"', ['@match' => $matches[1]]);
      return $matches[1];
    }

    // Паттерн 3: проверяем, не начинается ли текст с цифры, даже без точки
    if (preg_match('/^(\d+)/u', $text, $matches)) {
      //\Drupal::logger('graph_project')->debug('Pattern 3 (digit start) matched: "@match"', ['@match' => $matches[1]]);
      return $matches[1];
    }

    //\Drupal::logger('graph_project')->debug('No number found at beginning of text');
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
      // Проверяем, есть ли родительский номер и существует ли текущий узел
      if (!empty($item['parent_number']) && isset($node_map[$item['number']])) {
        $parent_id = $node_map[$item['parent_number']] ?? NULL;

        if ($parent_id) {
          // Загружаем узел заново (чтобы избежать проблем с ссылками)
          $node = $node_storage->load($node_map[$item['number']]);

          /*if ($node) {
            // Правильный формат для entity reference поля [citation:3]
            $node->set('field_parent_item', ['target_id' => $parent_id]);
            $node->save();

            \Drupal::logger('graph_project')->debug('Set parent @parent for node @child', [
              '@parent' => $parent_id,
              '@child' => $node->id(),
            ]);
          }*/
        } else {
          \Drupal::logger('graph_project')->debug('Parent not found for @num (parent @parent)', [
            '@num' => $item['number'],
            '@parent' => $item['parent_number'],
          ]);
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
      //\Drupal::logger('graph_project')->debug('getContent() type: @type', ['@type' => gettype($content)]);
      if (is_array($content)) {
        //\Drupal::logger('graph_project')->debug('getContent() count: @count', ['@count' => count($content)]);
      } elseif (is_object($content)) {
        //\Drupal::logger('graph_project')->debug('getContent() class: @class', ['@class' => get_class($content)]);
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
