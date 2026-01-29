<?php

namespace Drupal\budget_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Budget Import routes.
 */
class BudgetDataController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new BudgetDataController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Display budget data.
   */
  public function viewData(Request $request) {
    // Если запрошен JSON формат
    if ($request->query->get('format') === 'json') {
      // Можно добавить параметр type для выбора таблицы
      $type = $request->query->get('type', 'incomes');
      return new JsonResponse($this->generateBudgetJson($type));
    }

    $build = [];

    // 1. ТАБЛИЦА ДОХОДОВ
    $build['incomes_title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Доходы бюджета'),
      '#attributes' => ['class' => ['section-title']],
    ];

    $incomes_data = $this->getTableData('budget_incomes');
    $build['incomes_table'] = $this->buildTable(
      $incomes_data['rows'],
      $incomes_data['header'],
      $this->t('Нет данных о доходах. Импортируйте данные сначала.')
    );

    // 2. ТАБЛИЦА РАСХОДОВ
    $build['expenses_title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Расходы бюджета'),
      '#attributes' => ['class' => ['section-title']],
      '#weight' => 10,
    ];

    $expenses_data = $this->getTableData('budget_expenses');
    $build['expenses_table'] = $this->buildTable(
      $expenses_data['rows'],
      $expenses_data['header'],
      $this->t('Нет данных о расходах. Импортируйте данные сначала.'),
      ['class' => ['expenses-table']]
    );

    // 3. СВОДНАЯ ИНФОРМАЦИЯ
    $build['summary'] = $this->buildSummary($incomes_data, $expenses_data);

    // 4. КНОПКИ ДЕЙСТВИЙ
    $build['actions'] = [
      '#type' => 'actions',
      '#weight' => 100,
      'import' => [
        '#type' => 'link',
        '#title' => $this->t('Импортировать данные'),
        '#url' => \Drupal\Core\Url::fromRoute('budget_import.import_form'),
        '#attributes' => ['class' => ['button', 'button--primary']],
      ],
      'json_incomes' => [
        '#type' => 'link',
        '#title' => $this->t('Доходы в JSON'),
        '#url' => \Drupal\Core\Url::fromRoute('budget_import.view_data')
          ->setOption('query', ['format' => 'json', 'type' => 'incomes']),
        '#attributes' => ['class' => ['button']],
      ],
      'json_expenses' => [
        '#type' => 'link',
        '#title' => $this->t('Расходы в JSON'),
        '#url' => \Drupal\Core\Url::fromRoute('budget_import.view_data')
          ->setOption('query', ['format' => 'json', 'type' => 'expenses']),
        '#attributes' => ['class' => ['button']],
      ],
    ];

    // 5. CSS СТИЛИ
    //$build['#attached']['library'][] = 'budget_import/table';

    return $build;
  }

  private function getTableData($table_name) {
    $query = $this->database->select($table_name, 'b')
      ->fields('b', ['year', 'category', 'amount'])
      ->orderBy('category')
      ->orderBy('year');

    $results = $query->execute()->fetchAll();

    // Группируем по категориям
    $categories = [];
    $all_years = [];

    foreach ($results as $row) {
      $categories[$row->category][$row->year] = $row->amount;
      $all_years[$row->year] = $row->year;
    }

    // Сортируем годы
    sort($all_years);

    // Если нет годов, используем дефолтные
    if (empty($all_years)) {
      $all_years = [2026, 2027, 2028];
    }

    // Формируем заголовок таблицы
    $header = ['Категория'];
    foreach ($all_years as $year) {
      $header[] = (string) $year;
    }
    $header[] = 'Всего';

    // Формируем строки таблицы
    $rows = [];
    $year_totals = array_fill_keys($all_years, 0);
    $grand_total = 0;

    foreach ($categories as $category => $years_data) {
      $row_data = [$category];
      $row_total = 0;

      foreach ($all_years as $year) {
        $amount = $years_data[$year] ?? 0;
        $row_data[] = [
          'data' => number_format($amount, 2, '.', ' '),
          'class' => ['number-cell'],
        ];
        $row_total += $amount;
        $year_totals[$year] += $amount;
      }

      $row_data[] = [
        'data' => number_format($row_total, 2, '.', ' '),
        'class' => ['number-cell', 'total-cell'],
      ];
      $grand_total += $row_total;

      $rows[] = $row_data;
    }

    // Итоговая строка "ВСЕГО"
    if (!empty($rows)) {
      $total_row = [['data' => '<strong>ВСЕГО</strong>', 'class' => ['total-row']]];

      foreach ($all_years as $year) {
        $total_row[] = [
          'data' => '<strong>' . number_format($year_totals[$year], 2, '.', ' ') . '</strong>',
          'class' => ['number-cell', 'total-row'],
        ];
      }

      $total_row[] = [
        'data' => '<strong>' . number_format($grand_total, 2, '.', ' ') . '</strong>',
        'class' => ['number-cell', 'total-row', 'grand-total'],
      ];

      $rows[] = $total_row;
    }

    return [
      'header' => $header,
      'rows' => $rows,
      'year_totals' => $year_totals,
      'grand_total' => $grand_total,
      'years' => $all_years,
    ];
  }

  /**
   * Строит таблицу из данных.
   */
  private function buildTable($rows, $header, $empty_text, $attributes = []) {
    $default_attributes = [
      'class' => ['budget-data-table', 'sticky-enabled'],
    ];

    $attributes = array_merge($default_attributes, $attributes);

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $empty_text,
      '#attributes' => $attributes,
    ];
  }

  /**
   * Строит сводную информацию.
   */
  private function buildSummary($incomes_data, $expenses_data) {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['budget-summary']],
      '#weight' => 5,
    ];

    // Считаем баланс по годам
    $balance_by_year = [];
    $years = array_unique(
      array_merge($incomes_data['years'] ?? [], $expenses_data['years'] ?? [])
    );

    sort($years);

    if (empty($years)) {
      return $build;
    }

    $build['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Сводная информация'),
      '#attributes' => ['class' => ['summary-title']],
    ];

    // Таблица баланса
    $balance_header = ['Год', 'Доходы', 'Расходы', 'Баланс'];
    $balance_rows = [];

    foreach ($years as $year) {
      $income = $incomes_data['year_totals'][$year] ?? 0;
      $expense = $expenses_data['year_totals'][$year] ?? 0;
      $balance = $income - $expense;

      $balance_class = $balance >= 0 ? 'positive-balance' : 'negative-balance';

      $balance_rows[] = [
        $year,
        ['data' => number_format($income, 2, '.', ' '), 'class' => ['number-cell']],
        ['data' => number_format($expense, 2, '.', ' '), 'class' => ['number-cell']],
        [
          'data' => number_format($balance, 2, '.', ' '),
          'class' => ['number-cell', 'balance-cell', $balance_class],
        ],
      ];
    }

    // Итоги
    $total_income = $incomes_data['grand_total'] ?? 0;
    $total_expense = $expenses_data['grand_total'] ?? 0;
    $total_balance = $total_income - $total_expense;

    $total_balance_class = $total_balance >= 0 ? 'positive-balance' : 'negative-balance';

    $balance_rows[] = [
      ['data' => '<strong>ИТОГО</strong>', 'class' => ['total-row']],
      [
        'data' => '<strong>' . number_format($total_income, 2, '.', ' ') . '</strong>',
        'class' => ['number-cell', 'total-row'],
      ],
      [
        'data' => '<strong>' . number_format($total_expense, 2, '.', ' ') . '</strong>',
        'class' => ['number-cell', 'total-row'],
      ],
      [
        'data' => '<strong>' . number_format($total_balance, 2, '.', ' ') . '</strong>',
        'class' => ['number-cell', 'total-row', 'balance-cell', $total_balance_class],
      ],
    ];

    $build['balance_table'] = [
      '#type' => 'table',
      '#header' => $balance_header,
      '#rows' => $balance_rows,
      '#attributes' => ['class' => ['balance-table']],
    ];

    return $build;
  }

  private function getBudgetDataForType($type_page) {
    $database = \Drupal::database();

    $query = $database->select('budget_'.$type_page, 'b')
      ->fields('b', ['category', 'year', 'amount'])
      ->orderBy('b.category')
      ->orderBy('b.year');

    $results = $query->execute()->fetchAll();

    // Группируем данные по категориям
    $categories = [];
    $all_years = [];

    foreach ($results as $row) {
      if (!isset($categories[$row->category])) {
        $categories[$row->category] = [
          'category' => $row->category,
          'years' => []
        ];
      }
      $categories[$row->category]['years'][$row->year] = (float) $row->amount;
      $all_years[$row->year] = $row->year;
    }

    // Сортируем годы
    sort($all_years);

    // Формируем итоговую строку "ВСЕГО"
    $totals = [];
    foreach ($all_years as $year) {
      $totals[$year] = 0;
      foreach ($categories as $category_data) {
        if (isset($category_data['years'][$year])) {
          $totals[$year] += $category_data['years'][$year];
        }
      }
    }

    // Формируем данные в нужном формате
    $data = [
      'id' => '4',
      'token' => md5(time()),
      'appViewTitle' => '04. Доходы бюджета (бюджет)',
      'appViewMetaData' => [
        'field' => []
      ],
      'data' => []
    ];

    // Добавляем заголовки колонок
    $data['appViewMetaData']['field'][] = [
      'id' => '1',
      'data_type' => 'NUMBER',
      'title' => '1'
    ];

    $field_id = 2;
    foreach ($all_years as $year) {
      $data['appViewMetaData']['field'][] = [
        'id' => (string) $field_id,
        'data_type' => 'NUMBER',
        'title' => (string) $year
      ];
      $field_id++;
    }

    // Добавляем строку "ВСЕГО" первой
    $all_fields = [
      ['id' => '31', 'value' => 'ВСЕГО, в т.ч.']
    ];

    $field_value_id = 32;
    foreach ($all_years as $year) {
      $all_fields[] = [
        'id' => (string) $field_value_id,
        'value' => $totals[$year]
      ];
      $field_value_id++;
    }

    $data['data'][] = [
      'row' => ['field' => $all_fields]
    ];

    // Добавляем остальные категории
    foreach ($categories as $category_data) {
      $category_fields = [
        ['id' => '31', 'value' => $category_data['category']]
      ];

      $field_value_id = 32;
      foreach ($all_years as $year) {
        $category_fields[] = [
          'id' => (string) $field_value_id,
          'value' => $category_data['years'][$year] ?? 0
        ];
        $field_value_id++;
      }

      $data['data'][] = [
        'row' => ['field' => $category_fields]
      ];
    }

    return $data;
  }

  private function generateBudgetJson($type_page) {
    //$database = \Drupal::database();

    if($type_page == 'all')
    {
      $data = [];
      $arTypes = ['incomes','expenses'];
      foreach ($arTypes as $type)
      {
        $data[$type.'Data'] = $this->getBudgetDataForType($type);
      }
    }
    else
    {
      $data = $this->getBudgetDataForType($type_page);
    }

    return $data;
  }

  /**
   * Generate JSON data in Highcharts format.
   */
  private function generateBudgetJson__() {
    $query = $this->database->select('budget_incomes', 'b')
      ->fields('b', ['category', 'year', 'amount'])
      ->orderBy('category')
      ->orderBy('year');

    $results = $query->execute()->fetchAll();
    $years = [2026,2027,2028];

    // Преобразуем в нужный формат
    $data = [
      'id' => '4',
      'token' => md5(time()),
      'appViewTitle' => '04. Доходы бюджета (бюджет)',
      'appViewMetaData' => [
        'field' => [
          ['id' => '1', 'data_type' => 'NUMBER', 'title' => '1'],
          ['id' => '2', 'data_type' => 'NUMBER', 'title' => $years[0]],
          ['id' => '3', 'data_type' => 'NUMBER', 'title' => $years[1]],
          ['id' => '4', 'data_type' => 'NUMBER', 'title' => $years[2]],
        ]
      ],
      'data' => []
    ];

    $categories = [];
    $totals = [$years[0] => 0, $years[1] => 0, $years[2] => 0];

    foreach ($results as $row) {
      if (!isset($categories[$row->category])) {
        $categories[$row->category] = ['category' => $row->category, 'years' => []];
      }
      $categories[$row->category]['years'][$row->year] = $row->amount;

      // Считаем итоги
      if (isset($totals[$row->year])) {
        $totals[$row->year] += $row->amount;
      }
    }

    // Добавляем строку "ВСЕГО" в начало
    $data['data'][] = [
      'row' => [
        'field' => [
          ['id' => '31', 'value' => 'ВСЕГО, в т.ч.'],
          ['id' => '32', 'value' => (float) $totals[$years[0]]],
          ['id' => '33', 'value' => (float) $totals[$years[1]]],
          ['id' => '34', 'value' => (float) $totals[$years[2]]],
        ]
      ]
    ];

    // Добавляем остальные категории
    foreach ($categories as $category_data) {
      $data['data'][] = [
        'row' => [
          'field' => [
            ['id' => '31', 'value' => $category_data['category']],
            ['id' => '32', 'value' => (float) ($category_data['years'][$years[0]] ?? 0)],
            ['id' => '33', 'value' => (float) ($category_data['years'][$years[1]] ?? 0)],
            ['id' => '34', 'value' => (float) ($category_data['years'][$years[2]] ?? 0)],
          ]
        ]
      ];
    }

    return $data;
  }

  /**
   * API endpoint for budget incomes data.
   */
  public function apiData(Request $request) {

    $type_page = $request->query->get('type_data', 'incomes');

    $data = $this->generateBudgetJson($type_page);

    // Можно вернуть в разных форматах
    $format = $request->query->get('format', 'json');

    if ($format === 'json') {
      $response = new JsonResponse($data);

      // Настраиваем CORS заголовки если нужно
      $response->headers->set('Access-Control-Allow-Origin', '*');
      $response->headers->set('Access-Control-Allow-Methods', 'GET');
      $response->headers->set('Content-Type', 'application/json; charset=utf-8');

      return $response;
    }

    // Или просто массив для Drupal
    return new JsonResponse($data);
  }

}
