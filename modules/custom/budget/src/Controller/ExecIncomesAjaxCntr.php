<?php

namespace Drupal\budget\Controller;

use Drupal\budget\MyHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\budget_import\Service\BudgetDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExecIncomesAjaxCntr extends ControllerBase {

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

    // для страницы /execution/incomes "(Исполнение) Доходы бюджета Екатеринбурга" на указанную дату или последнюю загруженную
    // берутся из базы доходы, плановые и фактические, по 2м категориям (налоговые и неналоговые, безвозмездные поступления)

    $arData = [];
    if (!empty($date)) {
      $arIncomes = $data['execution']['income'][$date] ?? [];
    } else {
      $date = key($data['execution']['income']);
      $arIncomes = reset($data['execution']['income']);
    }


    foreach ($arIncomes as $categ => $arCats)
    {
        $arData[] = ['row' => ['field' => [['id'=>'69', 'value' => $categ], ['id'=>'70', 'value' => $arCats['plan']], ['id'=>'71', 'value' => $arCats['actual']], ['id'=>'72', 'value' => $date]]]];
    }


    return new JsonResponse([
      'success' => true,
      'data' => $arData
    ]);
  }
}
