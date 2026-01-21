<?php
namespace Drupal\budget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;


/**
 * Provides a 'Budget' block.
 *
 * @Block(
 *   id = "budget",
 *   admin_label = @Translation("Budget"),
 *   category = @Translation("Budget")
 * )
 */

class Budget extends BlockBase
{
  public function build()
  {

    $build = [];

    // Контейнер для графика
    $build['crumb'] = [
      '#markup' => '<div class="breadcrumbs"><a href="/">Главная</a>
                  <div>|</div><span>Бюджет Екатеринбурга</span>
                  <div>|</div><span>Основные показатели бюджета</span></div>',
    ];

    // Loader (если есть в вашем HTML)
    $build['h1'] = [
      '#markup' => '<h1>Основные показатели бюджета, млн руб.</h1>',
    ];

    return $build;
  }
}
