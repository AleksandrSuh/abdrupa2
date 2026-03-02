<?php

namespace Drupal\graph_project\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for displaying budget graphics.
 */
class GraphicsController extends ControllerBase {

  /**
   * Displays the budget graphics page.
   */
  public function page() {
    // Получаем последний импортированный год
    $config = $this->config('graph_project.settings');
    $last_import = $config->get('last_import');
    \Drupal::logger('graph_project')->debug('last_import: @import', [
      '@import' => print_r($last_import, TRUE)
    ]);
    $year = $last_import['year'] ?? date('Y');
    $plan_years = $last_import['plan_years'];

    $file_url = '';
    $file_name = '';
    if (!empty($last_import['fid'])) {
      $file = File::load($last_import['fid']);
      if ($file) {
        $file_url = $file->createFileUrl();
        $file_name = $file->getFilename();
      }
    } elseif (!empty($last_import['filename'])) {
      // Если сохранили только имя, пытаемся найти файл
      $file_url = file_create_url('public://graph_project/' . $last_import['filename']);
      $file_name = $last_import['filename'];
    }
    // Получаем все пункты за этот год
    $items = $this->getBudgetItems($year);

    // Добавляем расчёт процентов для полос
    $items = $this->calculatePositions($items, $year);

    return [
      '#theme' => 'graph_project_page',
      '#items' => $items,
      '#year' => $year,
      '#plan_years' => $plan_years,
      '#file_url' => $file_url,
      '#file_name' => $file_name,
      //'#template_type' => $template_type,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Gets all budget items for a given year.
   */
  private function getBudgetItems($year) {
    $node_storage = $this->entityTypeManager()->getStorage('node');

    $nodes = $node_storage->loadByProperties([
      'type' => 'graph_item',
      'field_year' => $year,
    ]);

    $items = [];
    foreach ($nodes as $node) {
      // Парсим дату из field_deadline_raw
      $deadline = $node->get('field_deadline_raw')->value;

      // Определяем месяц для позиционирования
      $month = $this->parseMonth($deadline);

      $items[] = [
        'nid' => $node->id(),
        'number' => $node->get('field_item_number')->value,
        'title' => $node->getTitle(),
        'full_text' => $node->get('field_item_text')->value,
        'deadline_raw' => $deadline,
        'deadline_month' => $month,
        'responsible' => $node->get('field_responsible')->value,
        'level' => (int) $node->get('field_level')->value,
        'parent_number' => $this->getParentNumber($node->get('field_item_number')->value),
        'classes' => $node->get('field_klassy_stili')->value,
      ];
    }

    // Сортируем по номеру пункта
    usort($items, function($a, $b) {
      return version_compare($a['number'], $b['number']);
    });

    return $items;
  }

  /**
   * Calculate left and right percentages based on deadlines.
   */
  private function calculatePositions($items, $year) {
    $months = [
      '07' => 'Июль',
      '08' => 'Август',
      '09' => 'Сентябрь',
      '10' => 'Октябрь',
      '11' => 'Ноябрь',
      '12' => 'Декабрь',
    ];

    $month_positions = [
      '07' => 0,
      '08' => 16.67,
      '09' => 33.33,
      '10' => 50,
      '11' => 66.67,
      '12' => 83.33,
    ];

    foreach ($items as &$item) {
      $deadline = $item['deadline_raw'];

      // Парсим дату (формат ДД.ММ.ГГГГ)
      if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $deadline, $matches)) {
        $day = $matches[1];
        $month = $matches[2];

        // Вычисляем позиции (хардкорные округлённые проценты)
        switch ($month) {
          case '07': // Июль
            $left = 0;
            $right = 100 - 16;
            break;
          case '08': // Август
            $left = 16;
            $right = 100 - 32;
            break;
          case '09': // Сентябрь
            $left = 32;
            $right = 100 - 48;
            break;
          case '10': // Октябрь
            $left = 48;
            $right = 100 - 64;
            break;
          case '11': // Ноябрь
            $left = 64;
            $right = 100 - 80;
            break;
          case '12': // Декабрь
            $left = 80;
            $right = 0;
            break;
          default:
            $left = 0;
            $right = 0;
        }

        $item['left'] = $left;
        $item['right'] = $right;
      } else {
        // Если дата не распарсилась (например, прочерк)
        $item['left'] = 40;
        $item['right'] = 30;
      }
    }

    return $items;
  }

  /**
   * Parse month from deadline string.
   */
  private function parseMonth($deadline) {
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $deadline, $matches)) {
      return $matches[2];
    }
    return null;
  }

  /**
   * Get parent number from item number.
   */
  private function getParentNumber($number) {
    $parts = explode('.', $number);
    array_pop($parts);
    return implode('.', $parts);
  }

}
