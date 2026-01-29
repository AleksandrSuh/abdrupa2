<?php

namespace Drupal\budget\Plugin\Block;

use Drupal\budget\MyHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'B I Chart' block.
 *
 * @Block(
 *   id = "bi_chart",
 *   admin_label = @Translation("BI Chart"),
 *   category = @Translation("Budget")
 * )
 */
class BICBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }
  public function build() {

    $build = [];
    $path_matcher = \Drupal::service('path.matcher');
    $is_front = $path_matcher->isFrontPage();
    //echo MyHelper::printPre(get_class_methods($node));
    //echo $node_url;
    if($is_front)
    {
      $build['#attached'] = [
        'library' => ['budget/incomes_expenses_chart_main'],
        'drupalSettings' => [
          'budget' => [
            'ajaxUrl' => \Drupal\Core\Url::fromRoute('budget_import.api_json')
              ->setOption('query', ['format' => 'json', 'type_data' => 'all'])
              ->toString(),
            'fallbackData' => [
              // Можно оставить пустым или добавить минимальные данные
            ]
          ]
        ]
      ];
    }
    else
    {
      $node = $this->routeMatch->getParameter('node');
      $node_url = $node->toUrl()->toString();
      if($node_url == '/budget/incomes')
      {
        $build['crumb'] = [
          '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                    <div>|</div><span>Бюджет Екатеринбурга</span>
                    <div>|</div><span>Доходы бюджета</span></div>',
          '#weight' => -1,
        ];
        $build['h1'] = [
          '#markup' => '<h1>Доходы бюджета</h1>',
          '#weight' => 0,
        ];
        // Контейнер для графика
        $build['chart'] = [
          '#markup' => '<div id="infographics-1">&nbsp;</div>',
          '#weight' => 2,
        ];

        // Loader (если есть в вашем HTML)
        $build['loader'] = [
          '#markup' => '<div id="ajaxLoader" class="ajaxLoader">&nbsp;</div>',
          '#weight' => 2,
        ];

        // Передаем URL для AJAX
        $build['#attached'] = [
          'library' => ['budget/incomes_chart2'],
          'drupalSettings' => [
            'budget' => [
              'ajaxUrl' => \Drupal\Core\Url::fromRoute('budget_import.api_json')
                ->setOption('query', ['format' => 'json', 'type_data' => 'incomes'])
                ->toString(),
              'fallbackData' => [
                  //'type' => 'incomes'
              ]
            ]
          ]
        ];
      }
      else //   /budget/expenses
      {

        $build['crumb'] = [
          '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                    <div>|</div><a href="/budget">Бюджет Екатеринбурга</a>
                    <div>|</div><a href="/budget/expenses">Расходы бюджета</a>
                    <div>|</div><span>Расходы в разрезе отраслей</span></div>',
          '#weight' => -1,
        ];
        $build['h1'] = [
          '#markup' => '<h1>Расходы бюджета</h1>',
          '#weight' => 0,
        ];
        // Контейнер для графика
        $build['chart'] = [
          '#markup' => '<div id="infographics-1">&nbsp;</div>',
          '#weight' => 2,
        ];

        // Loader (если есть в вашем HTML)
        $build['loader'] = [
          '#markup' => '<div id="ajaxLoader" class="ajaxLoader">&nbsp;</div>',
          '#weight' => 2,
        ];

        // Передаем URL для AJAX
        $build['#attached'] = [
          'library' => ['budget/incomes_chart2'],
          'drupalSettings' => [
            'budget' => [
              'ajaxUrl' => \Drupal\Core\Url::fromRoute('budget_import.api_json')
                ->setOption('query', ['format' => 'json', 'type_data' => 'expenses'])
                ->toString(),
              'fallbackData' => [
                //'type' => 'expenses'
              ]
            ]
          ]
        ];
      }
    }

    $build['#cache'] = [
      'contexts' => ['url.path'], // Разный кэш для разных урл
      'tags' => $this->getCacheTags(), // Теги для инвалидации при изменении данных
    ];


    return $build;


    $data = [
      ['name' => 'Пеньки', 'y' => 300],
      ['name' => 'Мороз', 'y' => 3400],
    ];

    // Передаём данные в JS через drupalSettings
    return [
      '#markup' => Markup::create(' <div id="ajaxLoader">&nbsp;</div><div id="infographics-1">&nbsp;</div>'),
      '#attached' => [
        'library' => ['budget/incomes_chart2'],
        'drupalSettings' => [
          'budgetIncomesData' => $data,
        ],
      ],
    ];
  }
}
