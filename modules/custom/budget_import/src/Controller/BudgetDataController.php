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
      return new JsonResponse($this->generateBudgetJson());
    }

    // Получаем данные из базы
    $query = $this->database->select('budget_incomes', 'b')
      ->fields('b', ['year', 'category', 'amount'])
      ->orderBy('category')
      ->orderBy('year');

    $results = $query->execute()->fetchAll();

    // Группируем по категориям
    $categories = [];
    foreach ($results as $row) {
      $categories[$row->category][$row->year] = $row->amount;
    }

    $years = [2026,2027,2028];

    // Формируем таблицу
    $header = ['Категория', $years[0], $years[1], $years[2], 'Всего'];
    $rows = [];

    $total_2025 = 0;
    $total_2026 = 0;
    $total_2027 = 0;

    foreach ($categories as $category => $years_n) {
      $amount_2025 = $years_n[$years[0]] ?? 0;
      $amount_2026 = $years_n[$years[1]] ?? 0;
      $amount_2027 = $years_n[$years[2]] ?? 0;
      $total = $amount_2025 + $amount_2026 + $amount_2027;

      $rows[] = [
        $category,
        [
          'data' => number_format($amount_2025, 2, '.', ' '),
          'class' => ['number-cell'],
        ],
        [
          'data' => number_format($amount_2026, 2, '.', ' '),
          'class' => ['number-cell'],
        ],
        [
          'data' => number_format($amount_2027, 2, '.', ' '),
          'class' => ['number-cell'],
        ],
        [
          'data' => number_format($total, 2, '.', ' '),
          'class' => ['number-cell', 'total-cell'],
        ],
      ];

      $total_2025 += $amount_2025;
      $total_2026 += $amount_2026;
      $total_2027 += $amount_2027;
    }

    // Итоговая строка
    $rows[] = [
      [
        'data' => '<strong>ВСЕГО</strong>',
        'class' => ['total-row'],
      ],
      [
        'data' => '<strong>' . number_format($total_2025, 2, '.', ' ') . '</strong>',
        'class' => ['number-cell', 'total-row'],
      ],
      [
        'data' => '<strong>' . number_format($total_2026, 2, '.', ' ') . '</strong>',
        'class' => ['number-cell', 'total-row'],
      ],
      [
        'data' => '<strong>' . number_format($total_2027, 2, '.', ' ') . '</strong>',
        'class' => ['number-cell', 'total-row'],
      ],
      [
        'data' => '<strong>' . number_format($total_2025 + $total_2026 + $total_2027, 2, '.', ' ') . '</strong>',
        'class' => ['number-cell', 'total-row', 'grand-total'],
      ],
    ];

    $build = [];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('Нет данных. Импортируйте данные сначала.'),
      '#attributes' => [
        'class' => ['budget-data-table', 'sticky-enabled'],
      ],
      '#attached' => [
        'library' => ['budget_import/table'],
      ],
    ];

    $build['actions'] = [
      '#type' => 'actions',
      'import' => [
        '#type' => 'link',
        '#title' => $this->t('Импортировать данные'),
        '#url' => \Drupal\Core\Url::fromRoute('budget_import.import_form'),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
        ],
      ],
      'json' => [
        '#type' => 'link',
        '#title' => $this->t('Получить данные в формате JSON'),
        '#url' => \Drupal\Core\Url::fromRoute('budget_import.view_data')
          ->setOption('query', ['format' => 'json']),
        '#attributes' => [
          'class' => ['button'],
        ],
      ],
    ];

    return $build;
  }

  private function generateBudgetJson() {
    $database = \Drupal::database();

    // Получаем все данные, отсортированные по категории и году
    $query = $database->select('budget_incomes', 'b')
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
    $data = $this->generateBudgetJson();

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
