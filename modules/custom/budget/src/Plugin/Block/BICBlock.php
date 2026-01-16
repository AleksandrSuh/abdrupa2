<?php

namespace Drupal\budget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Provides a 'B I Chart' block.
 *
 * @Block(
 *   id = "bi_chart",
 *   admin_label = @Translation("BI Chart"),
 *   category = @Translation("Budget")
 * )
 */
class BICBlock extends BlockBase {

  public function build() {
    $data = [
      ['name' => 'Пеньки', 'y' => 300],
      ['name' => 'Мороз', 'y' => 3400],
    ];

    // Передаём данные в JS через drupalSettings
    return [
      '#markup' => Markup::create(' <div id="ajaxLoader">&nbsp;</div><div id="infographics-1">&nbsp;</div>'),
      '#attached' => [
        'library' => ['budget/incomes_chart2'],
        'drupalSettings' => [
          'budgetIncomesData' => $data,
        ],
      ],
    ];
  }
}
