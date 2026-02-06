<?php

namespace Drupal\budget_execution\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

class ImportXlsxFormE extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'budget_import_xlsx_form';
  }

  private $cl_name = 'ImportXlsxFormE',
    $table_name = 'budget_execution_base';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<div class="messages messages--status">
        <p>Загрузите XLSX-файл "Исполнение бюджета". Форматы файла определены, нарушение форматов ведёт к взысканию.</p>
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
      '#url' => Url::fromRoute('entity.budget_execution.collection'),
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
    $clear_existing = false;//$form_state->getValue('clear_existing');

    $file = File::load($file_id);

    if ($file) {
      $file_uri = $file->getFileUri();
      $file_path = \Drupal::service('file_system')->realpath($file_uri);

      try {
        if ($clear_existing) {
          $this->clearExistingData();
        }

        $imported_incomes = $this->importXlsxData($file_path);
        //$imported_expenses = $this->importXlsxData($file_path, 'expenses');

        if(intval($imported_incomes))
        {
          $this->messenger()->addMessage(
            $this->t('Успешно записано строк: @count1 .', ['@count1' => $imported_incomes])
          );
        }


        // Перенаправляем на просмотр данных
        $form_state->setRedirect('entity.budget_execution.collection');
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
      COUNT(DISTINCT date) as date_count,
      COUNT(DISTINCT category_name) as categories_count,
      MIN(date) as min_year,
      MAX(date) as max_year
    FROM {".$this->table_name."}
  ")->fetchAssoc();

    $this->messenger()->addMessage(
      $this->t('Статистика базы данных: всего @total записей, @month месяцев, @categories категорий, период: @min - @max .', [
        '@total' => $stats['total'],
        '@month' => $stats['date_count'],
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
    //$connection->truncate('budget_incomes')->execute();
    $this->messenger()->addMessage($this->t('Существующие данные очищены.'));
  }

  /**
   * Import data from XLSX file.
   */
  private function importXlsxData($file_path) {
    if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
      throw new \Exception('Установите PhpSpreadsheet: ddev composer require phpoffice/phpspreadsheet');
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $table_name = $this->table_name;
    $sheetCount = $spreadsheet->getSheetCount();
    $arSheets = [];
    $check_doc = false;
    for ($i=0; $i < $sheetCount; $i++)
    {
      $arSheets[$i] = $spreadsheet->getSheet($i);
      $sheet_name = $arSheets[$i]->getTitle();
    }
    if($sheetCount == 1 && $sheet_name === 'Доходы')
    {
      $check_doc = 'income';
    }

    \Drupal::logger('budget_execut_import')->info(
      'Используем лист: @sheet ',
      ['@sheet' => $sheet_name]
    );

    if($check_doc)
    {
      $imported_count = 0;
      if($check_doc == 'income')
      {
        $highestRow = $arSheets[0]->getHighestRow();

        $start_row = 2;          // Данные начинаются с строки 2

        $code_column = 'B';
        $date_column = 'D';
        $value_plan_column = 'E';
        $value_actual_column = 'F';
        $category_column = 'G';  // Колонка F = Название категории
      }
      else
      {
        $this->messenger()->addMessage(
          $this->t('Не удалось распознать файл (проверьте имена листов: "@name" и их количество: @count)',
            ['@count' => $sheetCount, '@name' => $sheet_name])
        );
        return false;
      }

    }
    else
    {
      $this->messenger()->addMessage(
        $this->t('Не удалось распознать файл (проверьте имена листов: "@name" и их количество: @count)',
          ['@count' => $sheetCount, '@name' => $sheet_name])
      );
      return false;
    }

    $arSheets[0]->getCell($date_column . $start_row)->getCalculatedValue(); // Принудительно вычисляем базовую ячейку //

    for ($row = $start_row; $row <= $highestRow; $row++) {

      $date_raw = $this->getCellValue($arSheets[0], $date_column . $row);
      $code_raw = $this->getCellValue($arSheets[0], $code_column . $row);
      $value_plan_raw = $this->getCellValue($arSheets[0], $value_plan_column . $row);
      $value_actual_raw = $this->getCellValue($arSheets[0], $value_actual_column . $row);
      $category_raw = $this->getCellValue($arSheets[0], $category_column . $row);

      // ДЕБАГ первых 10 строк
      if ($row <= $start_row + 10) {
        \Drupal::logger($this->cl_name)->debug(
          'Чтение строки @row: D="@year", E="@value", F="@category"',
          [
            '@row' => $row,
            '@date' => $date_raw ?? 'NULL',
            '@value_plan' => $value_plan_raw ?? 'NULL',
            '@value_act' => $value_actual_raw ?? 'NULL',
            '@category' => $category_raw ?? 'NULL'
          ]
        );
      }

      $code = trim($code_raw ?? '');
      $date = trim($date_raw ?? '');
      $value_plan = trim($value_plan_raw ?? '');
      $value_act = trim($value_actual_raw ?? '');
      $category = trim($category_raw ?? '');

      // Пропускаем пустые строки
      if (($category_column && empty($category)) || empty($date)) {
        continue;
      }

      if (is_string($date) && strpos($date, '=') === 0) {
        \Drupal::logger($this->cl_name)->warning(
          'Дата - формула в строке @row: @formula',
          ['@row' => $row, '@formula' => $date]
        );
        continue;
      }

      // Парсим значение
      $parsed_value_plan = $this->parseRussianNumber($value_plan);
      $parsed_value_act = $this->parseRussianNumber($value_act);

      if ($parsed_value_plan === null || $parsed_value_act === null) {
        \Drupal::logger($this->cl_name)->warning(
          'Не удалось распарсить значение: "@value" или "@value2" в строке @row',
          ['@value' => $parsed_value_plan, '@value2' => $parsed_value_act, '@row' => $row]
        );
        continue;
      }

      // Сохраняем
      if ($this->saveBudgetData($code, $date, $category, $parsed_value_plan, $parsed_value_act, $check_doc)) {
        $imported_count++;

        // ДЕБАГ первых 3 записей
        if ($imported_count <= 3) {
          \Drupal::logger($this->cl_name)->info(
            'Успешно импортировано: @year - "@category" = @amount',
            ['@year' => $date, '@category' => $category, '@amount' => $parsed_value_act]
          );
        }
      }
    }

    \Drupal::logger($this->cl_name)->info('Импорт завершен: @count записей', ['@count' => $imported_count]);

    return $imported_count;
  }

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
   * Generate UUID for a record based on key fields.
   */
  private function generateUuid($type, $category_code, $date) {
    // Создаём детерминированный UUID на основе ключевых полей
    // Это гарантирует, что для одинаковых данных будет одинаковый UUID
    $seed = implode('|', [
      $type,
      $category_code,
      $date
    ]);

    // Используем Drupal UUID сервис
    return \Drupal::service('uuid')->generate($seed);
  }

  private function saveBudgetData($code, $date, $category, $amount_plan, $amount_act, $type) {

    if (!preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $date, $matches)) {
      \Drupal::logger('budget_import')->error(
        'Неправильный формат даты в импорте: @date',
        ['@date' => $date]
      );
      return false;
    }

    [$month, $year] = [$matches[2], $matches[3]];


    $connection = \Drupal::database();
    $time = \Drupal::time()->getRequestTime();

    try {
      // Генерируем UUID на основе ключевых полей
      $uuid = $this->generateUuid($type, $code, $date);

      // Подготавливаем данные
      $arKeys = [
        'type' => $type,
        'category_code' => $code,
        'date' => $date
      ];

      $arFields = [
        'uuid' => $uuid,
        'category_code' => $code,
        'category_name' => $category,
        'date' => $date,
        'plan_value' => (int) $amount_plan,
        'actual_value' => (int) $amount_act,
        'type' => $type,
        'created' => $time,
        'changed' => $time,
        'uid' => \Drupal::currentUser()->id(),
        'year' => (int) $year,
        'month' => (int) $month
      ];

      // Используем merge для UPSERT (обновить или вставить)
      $query = $connection->merge($this->table_name)
        ->keys($arKeys)  // Уникальные ключи для поиска существующей записи
        ->fields($arFields); // Поля для обновления/вставки

      $result = $query->execute();

      // Логируем результат
      //$operation = ($result == \Drupal\Core\Database\Connection::MERGE_INSERT) ? 'INSERT' : 'UPDATE';
      \Drupal::logger($this->cl_name)->debug(
        'Сохранение: type=@type, code=@code, date=@date, uuid=@uuid',
        [
          //'@op' => $operation,
          '@type' => $type,
          '@code' => $code,
          '@date' => $date,
          '@uuid' => $uuid
        ]
      );

      return true;
    }
    catch (\Exception $e) {
      \Drupal::logger('budget_import')->error(
        'Ошибка сохранения: type=@type, code=@code, date=@date, категория=@category, ошибка=@error',
        [
          '@type' => $type,
          '@code' => $code,
          '@date' => $date,
          '@category' => $category,
          '@error' => $e->getMessage()
        ]
      );
      return false;
    }
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
        \Drupal::logger($this->cl_name)->debug(
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
