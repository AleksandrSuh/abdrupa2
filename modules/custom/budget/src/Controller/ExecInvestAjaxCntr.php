<?php

namespace Drupal\budget\Controller;

use Drupal\budget\MyHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\budget_import\Service\BudgetDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExecInvestAjaxCntr extends ControllerBase {

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

    // Получаем данные
    $data = $this->budgetDataService->getBudgetData();

    // для страницы /execution/investment "(Исполнение) Бюджетные инвестиции муниципального образования «город Екатеринбург»"
    // на указанную дату или последнюю загруженную
    // берутся из базы

    $arData = [];
    if (!empty($date)) {
      $arIncomes = $data['execution']['invest'][$date] ?? [];
    } else {
      $date = key($data['execution']['invest']);
      $arIncomes = reset($data['execution']['invest']);
    }


    foreach ($arIncomes as $categ => $arCats)
    {
        $percent = number_format($arCats['actual'] / $arCats['plan'] * 100, 1);
        $arData[] = ['row' => ['field' => [
          ['id'=>'78', 'value' => $arCats['code']],
          ['id'=>'73', 'value' => $arCats['code']],
          ['id'=>'74', 'value' => $categ],
          ['id'=>'75', 'value' => $arCats['plan']],
          ['id'=>'76', 'value' => $arCats['actual']],
          ['id'=>'77', 'value' => $percent],
          ['id'=>'79', 'value' => $date]
        ]]];
    }


    return new JsonResponse([
      'success' => true,
      'data' => $arData
    ]);
  }
}
