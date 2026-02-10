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

    // Обработка данных как в блоке
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

    // Форматирование...
    $arData['dohod']['plan'] = number_format(round($arData['dohod']['plan'] / 1000000), 0, '.', ' ');
    $arData['dohod']['actual'] = number_format(round($arData['dohod']['actual'] / 1000000), 0, '.', ' ');

    $actual = floatval(str_replace(' ', '', $arData['dohod']['actual']));
    $plan = floatval(str_replace(' ', '', $arData['dohod']['plan']));
    $arData['dohod']['percent'] = $plan > 0 ? number_format(round($actual / $plan * 100, 1), 1, ',') : 0;

    $arData['rashod']['plan'] = number_format(round($arData['rashod']['plan'] / 1000000), 0, '.', ' ');
    $arData['rashod']['actual'] = number_format(round($arData['rashod']['actual'] / 1000000), 0, '.', ' ');

    $actual = floatval(str_replace(' ', '', $arData['rashod']['actual']));
    $plan = floatval(str_replace(' ', '', $arData['rashod']['plan']));
    $arData['rashod']['percent'] = $plan > 0 ? number_format(round($actual / $plan * 100, 1), 1, ',') : 0;

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
