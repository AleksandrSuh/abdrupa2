<?php

namespace Drupal\budget_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Provides a form for importing XLSX files.
 */
class ImportXlsxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'budget_import_xlsx_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<div class="messages messages--status">
        <p>Загрузите XLSX-файл "Проект бюджета". Формат файла:</p>
        <p>Лист "Доходы (план)"</p>
        <ul>
          <li>Столбец A,B,C: игнорировать</li>
          <li>Столбец D: год</li>
          <li>Столбец E: Значение (с разделителем тысяч пробелом и запятой для копеек)</li>
          <li>Столбец F: Название категории</li>
        </ul>
        <p>1,2-я строки - заголовки.</p>
      </div>',
    ];

    $form['xlsx_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('XLSX файл'),
      '#description' => $this->t('Выберите файл Excel (.xlsx или .xls)'),
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'xlsx xls',
        ],
      ],
      '#upload_location' => 'public://budget_import/',
      '#required' => TRUE,
    ];

    $form['clear_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Очистить существующие данные перед импортом'),
      '#default_value' => FALSE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Импортировать'),
      '#button_type' => 'primary',
    ];

    $form['actions']['view_data'] = [
      '#type' => 'link',
      '#title' => $this->t('Просмотр данных'),
      '#url' => Url::fromRoute('budget_import.view_data'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('xlsx_file')[0] ?? NULL;

    if (!$file_id) {
      $form_state->setErrorByName('xlsx_file', $this->t('Пожалуйста, выберите файл.'));
      return;
    }

    $file = File::load($file_id);
    if (!$file) {
      $form_state->setErrorByName('xlsx_file', $this->t('Ошибка загрузки файла.'));
      return;
    }

    // Проверяем расширение
    $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
    $allowed_extensions = ['xlsx', 'xls'];

    if (!in_array($extension, $allowed_extensions)) {
      $form_state->setErrorByName('xlsx_file',
        $this->t('Файл должен быть в формате Excel (.xlsx, .xls). Текущий формат: .@ext', [
          '@ext' => $extension
        ])
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('xlsx_file')[0];
    $clear_existing = $form_state->getValue('clear_existing');

    $file = File::load($file_id);

    if ($file) {
      $file_uri = $file->getFileUri();
      $file_path = \Drupal::service('file_system')->realpath($file_uri);

      try {
        if ($clear_existing) {
          $this->clearExistingData();
        }

        $imported_incomes = $this->importXlsxData($file_path, 'incomes');
        $imported_expenses = $this->importXlsxData($file_path, 'expenses');
        $imported_mundolg = $this->importXlsxData($file_path, 'mundolg');
        $imported_inc_deficit = $this->importXlsxData($file_path, 'inc_deficit');

        $this->messenger()->addMessage(
          $this->t('Успешно записано строк: @count1 доходов, @count2 расходов.', ['@count1' => $imported_incomes, '@count2' => $imported_expenses])
        );

        // Перенаправляем на просмотр данных
        $form_state->setRedirect('budget_import.view_data');
      }
      catch (\Exception $e) {
        $this->messenger()->addError(
          $this->t('Ошибка при импорте: @error', ['@error' => $e->getMessage()])
        );
      }
    }
    $database = \Drupal::database();
    $stats = $database->query("
    SELECT
      COUNT(*) as total,
      COUNT(DISTINCT year) as years_count,
      COUNT(DISTINCT category) as categories_count,
      MIN(year) as min_year,
      MAX(year) as max_year
    FROM {budget_incomes}
  ")->fetchAssoc();

    $this->messenger()->addMessage(
      $this->t('Статистика базы данных: всего @total записей, @years лет, @categories категорий, период: @min - @max год.', [
        '@total' => $stats['total'],
        '@years' => $stats['years_count'],
        '@categories' => $stats['categories_count'],
        '@min' => $stats['min_year'],
        '@max' => $stats['max_year']
      ])
    );
  }

  /**
   * Clear existing budget data.
   */
  private function clearExistingData() {
    $connection = \Drupal::database();
    $connection->truncate('budget_incomes')->execute();
    $connection->truncate('budget_expenses')->execute();
    $connection->truncate('budget_mundolg')->execute();
    $connection->truncate('budget_inc_deficit')->execute();
    $this->messenger()->addMessage($this->t('Существующие данные очищены.'));
  }

  /**
   * Import data from XLSX file.
   */
  private function importXlsxData($file_path, $type = 'incomes') {
    if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
      throw new \Exception('Установите PhpSpreadsheet: ddev composer require phpoffice/phpspreadsheet');
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $table_name = 'budget_incomes';
    if ($type === 'incomes') {
      $worksheet = $spreadsheet->getSheet(0);  // Первый лист - доходы
    } elseif ($type === 'expenses') {
      $worksheet = $spreadsheet->getSheet(1);  // Второй лист - расходы
      $table_name = 'budget_expenses';
    } elseif ($type === 'mundolg') {
      $worksheet = $spreadsheet->getSheet(3);  // Четвёртый лист - муниципальный долг
      $table_name = 'budget_mundolg';
    } elseif ($type === 'inc_deficit') {
      $worksheet = $spreadsheet->getSheet(4);  // Пятый лист - источники финансирования дефицита бюджета
      $table_name = 'budget_inc_deficit';
    }

    \Drupal::logger('budget_import')->info(
      'Используем лист: @sheet ',
      ['@sheet' => $worksheet->getTitle()]
    );

    $imported_count = 0;
    $highestRow = $worksheet->getHighestRow();

    $start_row = 3;          // Данные начинаются с строки 3
    if($type === 'incomes' || $type === 'inc_deficit')
    {
      $year_column = 'D';      // Колонка D = Год
      $value_column = 'E';     // Колонка E = Значение
      $category_column = 'F';  // Колонка F = Название категории
    } elseif ($type === 'expenses') {
      $year_column = 'E';      // Год
      $value_column = 'F';     //  Значение
      $category_column = 'G';  // Название категории
    } elseif ($type === 'mundolg') {
      $year_column = 'A';      // Год
      $value_column = 'B';     //  Значение
      $category_column = false;
      $start_row = 2;          // Данные начинаются с строки 3
    }


    // Сначала вычислим все значения в колонке года
    // чтобы формулы ссылались на правильные вычисленные значения
    $worksheet->getCell($year_column . $start_row)->getCalculatedValue(); // Принудительно вычисляем базовую ячейку

    for ($row = $start_row; $row <= $highestRow; $row++) {
      // Используем метод getCellValue для поддержки формул
      $year_raw = $this->getCellValue($worksheet, $year_column . $row);
      $value_raw = $this->getCellValue($worksheet, $value_column . $row);
      if($category_column)
      {
        $category_raw = $this->getCellValue($worksheet, $category_column . $row);
      }


      // ДЕБАГ первых 10 строк
      if ($row <= $start_row + 10) {
        \Drupal::logger('budget_import')->debug(
          'Чтение строки @row: D="@year", E="@value", F="@category"',
          [
            '@row' => $row,
            '@year' => $year_raw ?? 'NULL',
            '@value' => $value_raw ?? 'NULL',
            '@category' => $category_raw ?? 'NULL'
          ]
        );
      }

      $year = trim($year_raw ?? '');
      $value = trim($value_raw ?? '');
      $category = trim($category_raw ?? '');


      // Пропускаем пустые строки
      if (($category_column && empty($category)) || empty($year)) {
        continue;
      }

      if($category_column)
      {
        // Пропускаем строки с "ВСЕГО", "Итого"
        if (stripos($category, 'ВСЕГО') !== false ||
          stripos($category, 'Всего') !== false ||
          stripos($category, 'Итого') !== false) {
          continue;
        }
      }

      // Проверяем, что год - это число (а не формула в текстовом виде)
      if (is_string($year) && strpos($year, '=') === 0) {
        \Drupal::logger('budget_import')->warning(
          'Год все еще формула в строке @row: @formula',
          ['@row' => $row, '@formula' => $year]
        );
        continue;
      }

      // Парсим год
      $year = (int) $year;
      if ($year < 2000 || $year > 2100) {
        \Drupal::logger('budget_import')->warning(
          'Некорректный год: @year в строке @row',
          ['@year' => $year, '@row' => $row]
        );
        continue;
      }

      // Парсим значение
      $parsed_value = $this->parseRussianNumber($value);

      if ($parsed_value === null) {
        \Drupal::logger('budget_import')->warning(
          'Не удалось распарсить значение: "@value" в строке @row',
          ['@value' => $value, '@row' => $row]
        );
        continue;
      }

      // Сохраняем
      if ($this->saveBudgetData($year, $category, $parsed_value, $table_name)) {
        $imported_count++;

        // ДЕБАГ первых 3 записей
        if ($imported_count <= 3) {
          \Drupal::logger('budget_import')->info(
            'Успешно импортировано: @year - "@category" = @amount',
            ['@year' => $year, '@category' => $category, '@amount' => $parsed_value]
          );
        }
      }
    }

    \Drupal::logger('budget_import')->info('Импорт завершен: @count записей', ['@count' => $imported_count]);

    return $imported_count;
  }

  /**
   * Парсит русский числовой формат: "24 451 000,00"
   */
  private function parseRussianNumber($value) {
    // Если это уже число
    if (is_numeric($value)) {
      return (float) $value;
    }

    // Убираем пробелы-разделители тысяч и заменяем запятую на точку
    $clean = str_replace(' ', '', $value);  // Убираем пробелы
    $clean = str_replace(',', '.', $clean); // Заменяем запятую на точку

    // Убираем нечисловые символы кроме минуса и точки
    $clean = preg_replace('/[^\d\.\-]/', '', $clean);

    if (is_numeric($clean)) {
      return (float) $clean;
    }

    // Попробуем другой подход - извлечь только цифры
    preg_match_all('/[\d,\.]+/', $value, $matches);
    if (!empty($matches[0])) {
      $number = str_replace([' ', ','], ['', '.'], $matches[0][0]);
      if (is_numeric($number)) {
        return (float) $number;
      }
    }

    return null;
  }

  /**
   * Обновленный saveBudgetData для лучшего логирования
   */
  private function saveBudgetData($year, $category, $amount, $table_name) {
    $connection = \Drupal::database();
    $time = \Drupal::time()->getRequestTime();

    try {
      /*$sql = <<<SQL
INSERT INTO {budget_incomes} (year, category, amount, created)
VALUES (:year, :category, :amount, :created)
ON CONFLICT (year, category)
DO UPDATE SET amount = :amount, created = :created
SQL;

      $connection->query($sql, [
        ':year' => $year,
        ':category' => $category,
        ':amount' => $amount,
        ':created' => $time,
      ]);*/
      $arKeys = ['year' => $year];
      $arFields = [
        'year' => $year,
        'amount' => $amount,
        'created' => $time,
      ];
      if(!empty($category))
      {
        $arKeys['category'] = $category;
        $arFields['category'] = $category;
      }



      $query = $connection->merge($table_name)
        ->keys($arKeys)  // Уникальные ключи
        ->fields($arFields);

      $query->execute();

      return true;
    }
    catch (\Exception $e) {
      \Drupal::logger('budget_import')->error(
        'Ошибка сохранения: год=@year, категория=@category, сумма=@amount, ошибка=@error',
        [
          '@year' => $year,
          '@category' => $category,
          '@amount' => $amount,
          '@error' => $e->getMessage()
        ]
      );
      return false;
    }
  }

  /**
   * Simple CSV import (temporary solution).
   */
  private function importSimpleCsvData($file_path) {
    $imported_count = 0;
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    if ($extension === 'csv') {
      $handle = fopen($file_path, 'r');
      if ($handle !== FALSE) {
        // Пропускаем заголовок
        fgetcsv($handle, 1000, ',');

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
          if (count($data) >= 4) {
            $category = trim($data[0]);

            // Годы 2025, 2026, 2027
            $years = [2025, 2026, 2027];

            for ($i = 1; $i <= 3; $i++) {
              if (isset($data[$i]) && is_numeric(trim($data[$i]))) {
                $amount = (float) trim($data[$i]);
                $year = $years[$i - 1];

                $this->saveBudgetData($year, $category, $amount);
                $imported_count++;
              }
            }
          }
        }
        fclose($handle);
      }
    } elseif (in_array($extension, ['xlsx', 'xls'])) {
      // Для XLSX файлов в Drupal 11
      $this->messenger()->addWarning(
        $this->t('Для импорта XLSX файлов установите PhpSpreadsheet: ddev composer require phpoffice/phpspreadsheet')
      );

      // Создаем тестовые данные
      $test_data = [
        [2025, 'Данные из XLSX', 1500000],
        [2026, 'Данные из XLSX', 1600000],
        [2027, 'Данные из XLSX', 1700000],
      ];

      foreach ($test_data as $data) {
        $this->saveBudgetData($data[0], $data[1], $data[2]);
        $imported_count++;
      }
    } else {
      throw new \Exception($this->t('Неподдерживаемый формат файла: @ext', ['@ext' => $extension]));
    }

    return $imported_count;
  }

  /**
   * Получает значение ячейки, включая вычисление формул.
   */
  private function getCellValue($worksheet, $cellAddress) {
    $cell = $worksheet->getCell($cellAddress);

    // Если это формула - получаем вычисленное значение
    if ($cell->isFormula()) {
      try {
        // Получаем вычисленное значение
        $calculatedValue = $worksheet->getCell($cellAddress)->getCalculatedValue();

        // ДЕБАГ: логируем формулы
        $formula = $cell->getValue();
        $result = $calculatedValue;
        \Drupal::logger('budget_import')->debug(
          'Формула: @cell = @formula => @result',
          ['@cell' => $cellAddress, '@formula' => $formula, '@result' => $result]
        );

        return $calculatedValue;
      }
      catch (\Exception $e) {
        \Drupal::logger('budget_import')->warning(
          'Ошибка вычисления формулы в @cell: @error',
          ['@cell' => $cellAddress, '@error' => $e->getMessage()]
        );
        return $cell->getValue(); // Возвращаем формулу как запасной вариант
      }
    }

    // Если не формула - просто значение
    return $cell->getValue();
  }



}
