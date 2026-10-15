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

    // Явный порядок строк - по коду категории.
    // Внутри одной даты порядок строк базой не определён (в SQL сортировка только по дате),
    // а страница подписывала строки по позиции - из-за этого числа путались местами.
    $arRows = [];
    foreach ($arIncomes as $categ => $arCats) {
      $arRows[] = [
        'name' => $categ,
        'plan' => $arCats['plan'] ?? 0,
        'actual' => $arCats['actual'] ?? 0,
        'code' => (string) ($arCats['code'] ?? ''),
      ];
    }
    usort($arRows, static function (array $a, array $b) {
      return strcmp($a['code'], $b['code']);
    });

    foreach ($arRows as $arRow) {
      $arData[] = ['row' => ['field' => [
        ['id' => '69', 'value' => $arRow['name']],
        ['id' => '70', 'value' => $arRow['plan']],
        ['id' => '71', 'value' => $arRow['actual']],
        ['id' => '72', 'value' => $date],
        // Код категории: по нему страница определяет подпись, а не по позиции строки.
        ['id' => '73', 'value' => $arRow['code']],
      ]]];
    }

    return new JsonResponse([
      'success' => true,
      'data' => $arData
    ]);
  }
}
