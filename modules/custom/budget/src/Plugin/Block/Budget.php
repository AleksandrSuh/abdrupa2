<?php
namespace Drupal\budget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\budget\MyHelper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\budget_import\Service\BudgetDataService;

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
   * Сервис данных.
   *
   * @var \Drupal\budget_import\Service\BudgetDataService
   */
  protected $budgetDataService;

  /**
   * Конструктор.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BudgetDataService $budgetDataService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('budget_import.data_service') // Вот здесь мы получаем сервис!
    );
  }

  public function build()
  {

    $build = [];

    // Контейнер для графика

    //$controller = new \Drupal\budget_import\Controller\BudgetDataController();
    //$data = $controller->viewData();

    $data = $this->budgetDataService->getBudgetData();

    $arData = [];
    foreach ($data as $type => $arBudData)
    {
      foreach ($arBudData as $year => $arCategs)
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

    $arData['deficit'] = [];
    foreach ($arData['incomes'] as $key => $arDatum)
    {
      $summ = $arDatum['summ'] - $arData['expenses'][$key]['summ'];
      $arData['deficit'][$key] = ['year' => $arDatum['year'], 'summ' => $summ, 'amount' => number_format($summ, 0, '.', ' ')];
    }


    echo MyHelper::printPre($arData);
    /*$build['#theme'] = 'budget_bl'; // нужно объявить эту тему в *.module файле.
    $build['#data'] = $data;
    $build['#crumb'] = '<div class="breadcrumbs"><a href="/">Главная</a>
                  <div>|</div><span>Бюджет Екатеринбурга</span>
                  <div>|</div><span>Основные показатели бюджета</span></div>';

    // Loader (если есть в вашем HTML)
    $build['#h1'] = '<h1>Основные показатели бюджета, млн руб.</h1>';

    return $build;*/

    return [
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
