<?php
namespace Drupal\budget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\budget\MyHelper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\budget_import\Service\BudgetDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Budget' block.
 *
 * @Block(
 *   id = "budget",
 *   admin_label = @Translation("Budget"),
 *   category = @Translation("Budget")
 * )
 */

class Budget extends BlockBase implements ContainerFactoryPluginInterface
{

  /**
   *
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   * @var \Drupal\budget_import\Service\BudgetDataService
   */

  protected $routeMatch;
  protected $budgetDataService;

  /**
   * Конструктор.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    RouteMatchInterface $route_match,
    BudgetDataService $budgetDataService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->budgetDataService = $budgetDataService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('budget_import.data_service') // Вот здесь мы получаем сервис!
    );
  }

  public function build()
  {

    $build = [];
    $request = \Drupal::request();
    $is_ajax = $request->isXmlHttpRequest();
    // Контейнер для графика

    //$controller = new \Drupal\budget_import\Controller\BudgetDataController();
    //$data = $controller->viewData();

    $data = $this->budgetDataService->getBudgetData();

    $arData = [];
    foreach ($data as $type => $arBudData)
    {
        foreach ($arBudData as $year => $arCategs)
        {
          if($type == 'mundolg')
          {
            $res_summ = $arCategs / 1000;
            $arData[$type][] = ['year' => $year, 'summ' => $res_summ, 'amount' => number_format($res_summ, 0, '.', ' ')];
          }
          elseif($type == 'incomes' || $type == 'expenses')
          {
            $summ = 0;
            foreach ($arCategs as $value)
            {
              $summ += $value;
            }
            $res_summ = $summ / 1000;
            $arData[$type][] = ['year' => $year, 'summ' => $res_summ, 'amount' => number_format($res_summ, 0, '.', ' ')];
          }
        }
    }

    $arData['deficit'] = [];
    foreach ($arData['incomes'] as $key => $arDatum)
    {
      $summ = $arDatum['summ'] - $arData['expenses'][$key]['summ'];
      $arData['deficit'][$key] = ['year' => $arDatum['year'], 'summ' => $summ, 'amount' => number_format($summ, 0, '.', ' ')];
    }
    $arData['date'] = key($data['execution']['income']);;
    $arIncomes = reset($data['execution']['income']);
    $arExpSector = reset($data['execution']['expense_sector']); // берём одну последнюю дату из базы

    $arData['dohod']['plan'] = $arData['dohod']['actual'] = $arData['rashod']['plan'] = $arData['rashod']['actual'] = 0;
    foreach ($arIncomes as $arVals)
    {
      $arData['dohod']['plan'] += $arVals['plan'];
      $arData['dohod']['actual'] += $arVals['actual'];
    }
    foreach ($arExpSector as $arVals)
    {
      $arData['rashod']['plan'] += $arVals['plan'];
      $arData['rashod']['actual'] += $arVals['actual'];
    }
    $arData['dohod']['plan'] = number_format(round($arData['dohod']['plan'] / 1000000), 0, '.', ' ');
    $arData['dohod']['actual'] = number_format(round($arData['dohod']['actual'] / 1000000), 0, '.', ' ');
    $actual = floatval($arData['dohod']['actual'] ?? 0);
    $plan = floatval($arData['dohod']['plan'] ?? 0);
    if ($plan > 0) {
      $arData['dohod']['percent'] = number_format(round($actual / $plan * 100, 1), 1, ',');
    } else {
      $arData['dohod']['percent'] = 0;
    }
    $arData['rashod']['plan'] = number_format(round($arData['rashod']['plan'] / 1000000), 0, '.', ' ');
    $arData['rashod']['actual'] = number_format(round($arData['rashod']['actual'] / 1000000), 0, '.', ' ');
    $actual = floatval($arData['rashod']['actual'] ?? 0);
    $plan = floatval($arData['rashod']['plan'] ?? 0);
    if ($plan > 0) {
      $arData['rashod']['percent'] = number_format(round($actual / $plan * 100, 1), 1, ',');
    } else {
      $arData['rashod']['percent'] = 0;
    }

    //echo MyHelper::printPre($arData);

    $path_matcher = \Drupal::service('path.matcher');
    $is_front = $path_matcher->isFrontPage();
    if($is_front)
    {
      $build = [
        'content' => [
          '#theme' => 'budget_bl_main',
          '#data' => $arData,
          '#weight' => 1,
        ],
      ];
    }
    else
    {
      $node = $this->routeMatch->getParameter('node');
      $node_url = $node->toUrl()->toString();
      if (strpos($node_url, 'execution') !== false)
      {
        //echo MyHelper::printPre($data);


        $build = [
          'crumb' => [
            '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                  <div>|</div><span>Исполнение бюджета</span>
                  <div>|</div><span>Основные показатели бюджета</span></div>',
          ],
          'h1' => [
            '#markup' => ' <h1>Исполнение основных показателей бюджета муниципального образования «город Екатеринбург» на <span class="js-header-date"></span> года</h1>',
            '#weight' => 0,
          ],
          'content' => [
            '#theme' => 'budget_execut_bl',
            '#data' => $arData,
            '#weight' => 1,
          ],
        ];
        $build['#attached'] = [
          'library' => ['budget/execution_index'],
          'drupalSettings' => [
            /*'budget' => [
              'ajaxUrl' => \Drupal\Core\Url::fromRoute('budget_import.api_json')
                ->setOption('query', ['format' => 'json', 'type_data' => 'inc_deficit'])
                ->toString(),
              'fallbackData' => [
                //'type' => 'incomes'
              ]
            ]*/
          ]
        ];
      }
      else
      {
        $build = [
          'crumb' => [
            '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                  <div>|</div><span>Бюджет Екатеринбурга</span>
                  <div>|</div><span>Основные показатели бюджета</span></div>',
          ],
          'h1' => [
            '#markup' => '<h1>Основные показатели бюджета, млн руб.</h1>',
            '#weight' => 0,
          ],
          'content' => [
            '#theme' => 'budget_bl',
            '#data' => $arData,
            '#weight' => 1,
          ],
        ];
      }

    }
    $build['#cache'] = [
      'contexts' => ['url.path'], // Разный кэш для разных урл
      'tags' => $this->getCacheTags(), // Теги для инвалидации при изменении данных
    ];
    if ($is_ajax) {
      $build['#cache']['max-age'] = 0;
      \Drupal::service('page_cache_kill_switch')->trigger();
    }
    return $build;

  }
}
