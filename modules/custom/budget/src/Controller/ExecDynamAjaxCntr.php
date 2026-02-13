<?php
/*
namespace Drupal\budget\Controller;

use Drupal\budget\MyHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\budget_import\Service\BudgetDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExecDynamAjaxCntr extends ControllerBase {

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

    // для страницы /execution/dynamics "(Исполнение) Динамика основных показателей бюджета Екатеринбурга
    // берутся из базы, ручное редактирование

        $arData = ['incomes' => $data['dynamic']['income'], 'expenses' => $data['dynamic']['expense']];


    return new JsonResponse([
      'success' => true,
      'data' => $arData
    ]);
  }
}
*/
