<?php

namespace Drupal\budget_execution\Controller;

use Drupal\budget\MyHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\budget_import\Service\BudgetDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
class BudgetExecutionController extends ControllerBase {
    public function dynamicIndicators() {
      $connection = \Drupal::database();

      // Группировка по годам и типам
      $query = $connection->select('budget_execution_indicators', 'b')
        ->fields('b', ['year', 'type', 'category', 'value'])
        ->orderBy('year', 'DESC')
        ->orderBy('type')
        ->orderBy('category');

      $results = $query->execute()->fetchAll();

      // Организуем данные для отображения
      $data = [];
      foreach ($results as $row) {
        $data[$row->year][$row->type][] = $row;
      }


      //\Drupal::logger('budget_execution')->debug('Data structure: @data', ['@data' => print_r($data, TRUE)]);

      /*$output = '<div class="budget-dynamic-indicators">';
      $output .= '<h2>Динамика основных показателей бюджета</h2>';

      if (empty($data)) {
        $output .= '<p>Нет данных для отображения</p>';
      } else {
        foreach ($data as $year => $types) {
          $output .= '<div class="year-block">';
          $output .= '<h3>' . $year . ' год</h3>';

          if (!empty($types['income'])) {
            $output .= '<div class="income-section">';
            $output .= '<h4>Доходы</h4>';
            $output .= '<table><thead><tr><th>Категория</th><th>Значение</th></tr></thead><tbody>';
            foreach ($types['income'] as $item) {
              $output .= '<tr><td>' . $item->category . '</td>';
              $output .= '<td>' . number_format($item->value, 2, '.', ' ') . '</td></tr>';
            }
            $output .= '</tbody></table></div>';
          }

          if (!empty($types['expense'])) {
            $output .= '<div class="expense-section">';
            $output .= '<h4>Расходы</h4>';
            $output .= '<table><thead><tr><th>Категория</th><th>Значение</th></tr></thead><tbody>';
            foreach ($types['expense'] as $item) {
              $output .= '<tr><td>' . $item->category . '</td>';
              $output .= '<td>' . number_format($item->value, 2, '.', ' ') . '</td></tr>';
            }
            $output .= '</tbody></table></div>';
          }

          $output .= '</div>';
        }
      }

      $output .= '</div>';

      return [
        '#markup' => $output,
        '#attached' => [
          'library' => [
            'budget_execution/budget_execution',
          ],
        ],
      ];

      // ВРЕМЕННО: Вернем простой массив для проверки
      return [
        '#markup' => '<pre>Данные загружены: ' . count($results) . ' записей</pre>',
        // '#theme' => 'budget_execution_dynamic_indicators',
        // '#data' => $data,
      ];*/

      return [
        '#theme' => 'budget_execution_dynamic_indicators',
        '#data' => $data,
        '#attached' => [
          'library' => [
            'budget/execution_index',
          ],
        ],
      ];
    }
}
?>
