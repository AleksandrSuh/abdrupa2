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

    $build = [];

    // Контейнер для графика
    $build['chart'] = [
      '#markup' => '<div id="infographics-1">&nbsp;</div>',
    ];

    // Loader (если есть в вашем HTML)
    $build['loader'] = [
      '#markup' => '<div id="ajaxLoader" class="ajaxLoader">&nbsp;</div>',
    ];

    // Передаем URL для AJAX
    $build['#attached'] = [
      'library' => ['budget/incomes_chart2'],
      'drupalSettings' => [
        'budget' => [
          'ajaxUrl' => \Drupal\Core\Url::fromRoute('budget_import.api_json')
            ->setOption('query', ['format' => 'json'])
            ->toString(),
          'fallbackData' => [
            // Можно оставить пустым или добавить минимальные данные
          ]
        ]
      ]
    ];

    return $build;


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
