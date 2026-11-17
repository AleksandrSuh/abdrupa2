<?php

namespace Drupal\budget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\budget_import\Service\BudgetDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExecutionAjaxController extends ControllerBase {

  protected $budgetDataService;

  public function __construct(BudgetDataService $budgetDataService) {
    $this->budgetDataService = $budgetDataService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('budget_import.data_service')
    );
  }

  public function getData(Request $request) {
    $date = $request->request->get('date');
    $is_ajax = $request->request->get('isAjaxRequest');

    // Получаем данные
    $data = $this->budgetDataService->getBudgetData();

    // для страницы /execution "Исполнение основных показателей бюджета МО "г.Екатеринбург" на указанную дату или последнюю загруженную
    // суммируются доходы и расходы, плановые и фактические, по отраслям
    $arData = [];
    if (!empty($date)) {
      $arData['date'] = $date;
      $arIncomes = $data['execution']['income'][$date] ?? [];
      $arExpSector = $data['execution']['expense_sector'][$date] ?? [];
    } else {
      $arData['date'] = key($data['execution']['income']);
      $arIncomes = reset($data['execution']['income']);
      $arExpSector = reset($data['execution']['expense_sector']);
    }

    // Расчеты...
    $arData['dohod']['plan'] = $arData['dohod']['actual'] = $arData['rashod']['plan'] = $arData['rashod']['actual'] = 0;

    foreach ($arIncomes as $arVals) {
      $arData['dohod']['plan'] += $arVals['plan'] ?? 0;
      $arData['dohod']['actual'] += $arVals['actual'] ?? 0;
    }

    foreach ($arExpSector as $arVals) {
      $arData['rashod']['plan'] += $arVals['plan'] ?? 0;
      $arData['rashod']['actual'] += $arVals['actual'] ?? 0;
    }

    // Форматирование. Проценты считаем от сырых сумм и ДО форматирования:
    // из отформатированной строки дробная часть теряется (именно из-за этого
    // процент на главной странице расходился с этой).
    // floor, а не round: сумма в миллионах показывается без завышения
    // (101602.55 млн -> 101 602, а не 101 603).
    $plan_raw = (float) $arData['dohod']['plan'];
    $actual_raw = (float) $arData['dohod']['actual'];
    $arData['dohod']['plan'] = number_format(floor($plan_raw / 1000000), 0, '.', ' ');
    $arData['dohod']['actual'] = number_format(floor($actual_raw / 1000000), 0, '.', ' ');
    $arData['dohod']['percent'] = $plan_raw > 0 ? number_format(round($actual_raw / $plan_raw * 100, 1), 1, ',') : 0;

    $plan_raw = (float) $arData['rashod']['plan'];
    $actual_raw = (float) $arData['rashod']['actual'];
    $arData['rashod']['plan'] = number_format(floor($plan_raw / 1000000), 0, '.', ' ');
    $arData['rashod']['actual'] = number_format(floor($actual_raw / 1000000), 0, '.', ' ');
    $arData['rashod']['percent'] = $plan_raw > 0 ? number_format(round($actual_raw / $plan_raw * 100, 1), 1, ',') : 0;

    // Рендерим только контентную часть
    $render_array = [
      '#theme' => 'budget_execut_bl',
      '#data' => $arData,
    ];

    $renderer = \Drupal::service('renderer');
    $content = $renderer->render($render_array);

    return new JsonResponse([
      'success' => true,
      'content' => (string) $content,
      'date' => $arData['date']
    ]);
  }
}
