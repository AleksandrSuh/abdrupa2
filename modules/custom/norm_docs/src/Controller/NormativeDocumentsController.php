<?php

namespace Drupal\norm_docs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class NormativeDocumentsController extends ControllerBase {

  /**
   * Отображает страницу с документами.
   *
   * @param string $_route_type
   *   Тип маршрута: 'normative' или 'budget'
   */
  public function page($_route_type = 'normative') {

    // Определяем настройки в зависимости от типа страницы
    $settings = $this->getPageSettings($_route_type);

    // Получаем термины таксономии
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($settings['vocabulary'], 0, NULL, TRUE);

    // Строим вывод
    $build = [
      '#theme' => $settings['theme'],
      '#documents_by_category' => [],
      '#page_title' => $settings['title'],
    ];

    foreach ($terms as $term) {
      // Проверяем, нужно ли показывать этот термин
      if (!$this->shouldShowTerm($term, $settings)) {
        continue;
      }

      // Получаем документы этой категории
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'docs')
        ->condition($settings['field_name'], $term->id())
        ->sort('created', 'ASC')
        ->accessCheck(TRUE);

      // Добавляем дополнительные фильтры для бюджетных документов
      if ($settings['filter']) {
        $query = $this->applyFilters($query, $settings['filter']);
      }

      $nids = $query->execute();

      if (!empty($nids)) {
        $build['#documents_by_category'][$term->id()] = [
          'term' => $term,
          'documents' => Node::loadMultiple($nids),
          'settings' => $settings,
        ];
      }
    }

    // Добавляем библиотеки
    $build['#attached']['library'][] = $settings['library'];

    // Кеширование
    $build['#cache']['tags'] = [
      'node_list:docs',
      'taxonomy_term_list:' . $settings['vocabulary'],
    ];

    return $build;
  }

  /**
   * Получает настройки для конкретной страницы.
   */
  private function getPageSettings($route_type) {
    $settings = [
      'normative' => [
        'title' => 'Нормативная правовая база',
        'theme' => 'normative_documents_page',
        'vocabulary' => 'document_categories',
        'field_name' => 'field_categ',
        'library' => 'norm_docs/normative-documents',
        'filter' => [], // Без дополнительных фильтров
        'show_empty_categories' => FALSE,
      ],
      'budget' => [
        'title' => 'Бюджетные документы',
        'theme' => 'budget_documents_page', // Другой шаблон
        'vocabulary' => 'budget_categories', // Другой словарь (можно создать)
        'field_name' => 'field_categ',
        'library' => 'norm_docs/budget-documents', // Другая библиотека
        'filter' => [
          'year_from' => 2020, // Только документы с 2020 года
        ],
        'show_empty_categories' => TRUE, // Показывать даже пустые категории
      ],
    ];

    return $settings[$route_type] ?? $settings['normative'];
  }

  /**
   * Проверяет, нужно ли показывать термин.
   */
  private function shouldShowTerm($term, $settings) {
    if ($settings['show_empty_categories']) {
      return TRUE;
    }

    // Проверяем, есть ли документы в этой категории
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'docs')
      ->condition($settings['field_name'], $term->id())
      ->count()
      ->accessCheck(TRUE);

    return $query->execute() > 0;
  }

  /**
   * Применяет дополнительные фильтры.
   */
  private function applyFilters($query, $filters) {
    if (isset($filters['year_from'])) {
      // Фильтр по дате создания
      $date = new \DateTime($filters['year_from'] . '-01-01');
      $query->condition('created', $date->getTimestamp(), '>=');
    }

    // Можно добавить другие фильтры по необходимости

    return $query;
  }
}
