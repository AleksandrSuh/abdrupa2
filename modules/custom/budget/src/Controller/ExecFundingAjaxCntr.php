<?php

namespace Drupal\budget\Controller;

use Drupal\budget\MyHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\budget_import\Service\BudgetDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExecFundingAjaxCntr extends ControllerBase {

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

    // для страницы /execution/funding "(Исполнение) Источники финансирования дефицита бюджета муниципального образования «город Екатеринбург» на указанную дату или последнюю загруженную
    // берутся из базы (импортированы из файла Исполнение лист Источники) источники, плановые и фактические

    $arData = [];
    if (!empty($date)) {
      $arIncomes = $data['execution']['source'][$date] ?? [];
    } else {
      $date = key($data['execution']['source']);
      $arIncomes = reset($data['execution']['source']);
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
