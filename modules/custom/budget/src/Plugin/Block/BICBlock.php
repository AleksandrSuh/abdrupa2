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
      if(strpos($node_url, 'budgetproject') !== false)
      {
        $crumb = 'Проект бюджета';
        $path = 'budgetproject';
      }
      elseif(strpos($node_url, 'execution') !== false)
      {
        $crumb = 'Исполнение бюджета';
        $path = 'execution';
      }
      else
      {
        $crumb = 'Бюджет Екатеринбурга';
        $path = 'budget';
      }
      // Контейнер для графика
      $build['chart'] = [
        '#markup' => '<div id="infographics-1">&nbsp;</div>',
        '#weight' => 2,
      ];
      $build['loader'] = [
        '#markup' => '<div id="ajaxLoader" class="ajaxLoader">&nbsp;</div>',
        '#weight' => 2,
      ];
      if($node_url == '/budget/incomes' || $node_url == '/budgetproject/incomes')
      {
        $build['crumb'] = [
          '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                    <div>|</div><span>'. $crumb .'</span>
                    <div>|</div><span>Доходы бюджета</span></div>',
          '#weight' => -1,
        ];
        $build['h1'] = [
          '#markup' => '<h1>Доходы бюджета</h1>',
          '#weight' => 0,
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
      elseif($node_url == '/budget/expenses' || $node_url == '/budgetproject/expenses')
      {

        $build['crumb'] = [
          '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                    <div>|</div><a href="/'. $path .'">'. $crumb .'</a>
                    <div>|</div><a href="/'. $path .'/expenses">Расходы бюджета</a>
                    <div>|</div><span>Расходы в разрезе отраслей</span></div>',
          '#weight' => -1,
        ];
        $build['h1'] = [
          '#markup' => '<h1>Расходы бюджета</h1>',
          '#weight' => 0,
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
      elseif($node_url == '/budget/funding' || $node_url == '/budgetproject/funding')   // /budget/funding  /budgetproject/funding
      {
        $build['crumb'] = [
          '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                    <div>|</div><a href="/'. $path .'">'. $crumb .'</a>
                    <div>|</div><span>Источники финансирования дефицита бюджета</span></div>',
          '#weight' => -1,
        ];
        $build['h1'] = [
          '#markup' => '<h1>Источники финансирования дефицита бюджета</h1>',
          '#weight' => 0,
        ];

        $build['#attached'] = [
          'library' => ['budget/inc_deficit_chart'],
          'drupalSettings' => [
            'budget' => [
              'ajaxUrl' => \Drupal\Core\Url::fromRoute('budget_import.api_json')
                ->setOption('query', ['format' => 'json', 'type_data' => 'inc_deficit'])
                ->toString(),
              'fallbackData' => [
                //'type' => 'incomes'
              ]
            ]
          ]
        ];
      }
      else // /execution/incomes
      {
        $build['crumb'] = [
          '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                    <div>|</div><span>'. $crumb .'</span>
                    <div>|</div><span>Доходы бюджета</span></div>',
          '#weight' => -1,
        ];
        $build['h1'] = [
          '#markup' => '<h1>Доходы бюджета Екатеринбурга, млн руб.</h1>',
          '#weight' => 0,
        ];
        $build['inp_date'] = [
          '#markup' => '<div class="table width-auto text-middle g-content__switcher__acts">
    <div class="table-cell" style="width:200px;">
        <label class="color-gray" style="font-size:15px;" for="">Данные представлены на:</label>
    </div>
    <div class="table-cell">
        <input class="date_select" id="from_data" type="text" value="31.12.2025" name="from_data">
    </div>
</div>',
          '#allowed_tags' => ['div', 'label', 'input', 'span', 'br', 'strong', 'em'],
          '#weight' => 0,
        ];

        $build['#attached'] = [
          'library' => ['budget/execution_chart'],
          'drupalSettings' => [
            'budget' => [
              'ajaxUrl' => \Drupal\Core\Url::fromRoute('budget_import.api_json')
                ->setOption('query', ['format' => 'json', 'type_data' => 'inc_deficit'])
                ->toString(),
              'fallbackData' => [
                //'type' => 'incomes'
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

  }
}
