<?php

namespace Drupal\budget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a 'Budget Incomes Chart' block.
 *
 * @Block(
 *   id = "budget_incomes_chart",
 *   admin_label = @Translation("Budget Incomes Chart"),
 *   category = @Translation("Budget")
 * )
 */
class BudgetIncomesChartBlock extends BlockBase {

  public function build() {
    // Пример: получаем данные из БД или сервиса
    $data = [
      ['name' => 'Налоги', 'y' => 1200],
      ['name' => 'Штрафы', 'y' => 300],
      ['name' => 'Пеньки', 'y' => 300],
      ['name' => 'Мороз', 'y' => 3400],
    ];

    // Передаём данные в JS через drupalSettings
    return [
      '#markup' => Markup::create(' <div id="ajaxLoader">&nbsp;</div><div id="infographics-1">&nbsp;</div>'),
      '#attached' => [
        'library' => ['budget/incomes_chart'],
        'drupalSettings' => [
          'budgetIncomesData' => $data,
        ],
      ],
    ];
  }
}
