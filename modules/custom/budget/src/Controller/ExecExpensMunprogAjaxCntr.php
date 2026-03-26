<?php

namespace Drupal\budget\Controller;

use Drupal\budget\MyHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\budget_import\Service\BudgetDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExecExpensMunprogAjaxCntr extends ControllerBase {

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

    // для страницы /execution/expenses_industries "(Исполнение) Расходы бюджета муниципального образования «город Екатеринбург» в разрезе отраслей"
    // на указанную дату или последнюю загруженную
    // берутся из базы расходы, плановые и фактические, по нескольким отраслям

    $arData = [];
    if (!empty($date)) {
      $arIncomes = $data['execution']['expense_program'][$date] ?? [];
    } else {
      $date = key($data['execution']['expense_program']);
      $arIncomes = reset($data['execution']['expense_program']);
    }


    $arSumm = [0,0];
    foreach ($arIncomes as $categ => $arCats)
    {
        $arData[] = ['row' => ['field' => [['id'=>'82', 'value' => $categ], ['id'=>'83', 'value' => $arCats['plan']], ['id'=>'84', 'value' => $arCats['actual']], ['id'=>'85', 'value' => $date]]]];
      $arSumm[0] += $arCats['plan'];
      $arSumm[1] += $arCats['actual'];
    }
    $arData[] = ['row' => ['field' => [['id'=>'82', 'value' => 'Суммарно'], ['id'=>'83', 'value' => $arSumm[0]], ['id'=>'84', 'value' => $arSumm[1]], ['id'=>'85', 'value' => $date]]]];


    return new JsonResponse([
      'success' => true,
      'data' => $arData
    ]);
  }
}
