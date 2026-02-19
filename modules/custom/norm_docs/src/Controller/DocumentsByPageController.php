<?php
// modules/custom/norm_docs/src/Controller/DocumentsByPageController.php

namespace Drupal\norm_docs\Controller;

use Drupal\budget\MyHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class DocumentsByPageController extends ControllerBase {

  /**
   * Отображает документы для конкретной страницы.
   *
   * @param string $_page_term
   *   Машиночитаемое имя термина страницы.
   */
  public function page($_page_term) {

    // 1. Находим термин по machine name
    $term = $this->findTermByMachineName($_page_term);

    if (!$term) {
      return [
        '#markup' => '<div class="messages messages--error">Страница не настроена.</div>',
      ];
    }

    // 2. Определяем тип страницы и соответствующие настройки
    $page_config = $this->getPageConfig($_page_term);

    // 3. Получаем документы в зависимости от конфигурации
    if ($page_config['group_by_category']) {
      // Для страниц, где нужна группировка по категориям (как /about/acts)
      $documents_by_category = $this->getDocumentsGroupedByCategory($term, $page_config);

      $build = [
        '#theme' => $page_config['theme'],
        '#documents_by_category' => $documents_by_category,
        '#page_term' => $term,
      ];
    } else {
      // Для страниц с простым списком
      $documents = $this->getDocumentsByPage($term, $page_config);
      $grouped_documents = $this->groupDocuments($documents);

      $build = [
        '#theme' => $page_config['theme'],
        '#documents' => $documents,
        '#grouped_documents' => $grouped_documents,
        '#page_term' => $term,
      ];
    }

    // 4. Общие настройки
    $build['#attached']['library'] = ['norm_docs/documents-page'];
    $build['#cache'] = [
      'tags' => [
        'node_list:docs',
        'taxonomy_term:' . $term->id(),
      ],
    ];
    //echo MyHelper::printPre($documents);

    return $build;
  }

  /**
   * Конфигурация для разных типов страниц.
   */
  private function getPageConfig($page_term) {
    $configs = [
      'normative' => [
        'theme' => 'documents_normative_page',
        'group_by_category' => true,           // Для /about/acts - группировка по категориям
        'category_field' => 'field_categ',      // Поле для категорий
        'sort_field' => 'created',
        'sort_order' => 'ASC',
      ],
      'budget_decision' => [
        'theme' => 'documents_budget_page',
        'group_by_category' => false,           // Простой список
        'sort_field' => 'created',
        'sort_order' => 'DESC',
      ],
      'project' => [
        'theme' => 'documents_projects_page',
        'group_by_category' => false,
        'sort_field' => 'created',
        'sort_order' => 'DESC',
      ],
      'report' => [
        'theme' => 'documents_reports_page',
        'group_by_category' => true,
        'category_field' => 'field_categ',
        'sort_field' => 'created',
        'sort_order' => 'DESC',
      ],
    ];

    return $configs[$page_term] ?? [
      'theme' => 'documents_default_page',
      'group_by_category' => false,
      'sort_field' => 'created',
      'sort_order' => 'DESC',
    ];
  }

  /**
   * Получает документы для конкретной страницы (простой список).
   */
  private function getDocumentsByPage($term, $config) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'docs')
      ->condition('field_doc_page', $term->id())
      ->sort($config['sort_field'], $config['sort_order'])
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    return Node::loadMultiple($nids);
  }

  /**
   * Получает документы с группировкой по категориям (как в старом коде).
   */
  private function getDocumentsGroupedByCategory($page_term, $config) {
    // Получаем все термины категорий (из словаря document_categories)
    $category_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('document_categories', 0, NULL, TRUE);

    $documents_by_category = [];

    foreach ($category_terms as $category_term) {
      // Получаем документы этой категории, которые также привязаны к нашей странице
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'docs')
        ->condition('field_categ', $category_term->id())
        ->condition('field_doc_page', $page_term->id())  // Важно! Только для этой страницы
        ->sort($config['sort_field'], $config['sort_order'])
        ->accessCheck(TRUE);

      $nids = $query->execute();

      if (!empty($nids)) {
        $documents_by_category[$category_term->id()] = [
          'term' => $category_term,
          'documents' => Node::loadMultiple($nids),
        ];
      }
    }

    return $documents_by_category;
  }

  /**
   * Группирует документы по годам.
   */
  private function groupDocuments($documents) {
    $grouped = [];
    foreach ($documents as $document) {
      $year = date('Y', $document->getCreatedTime());
      $grouped[$year][] = $document;
    }
    krsort($grouped);
    return $grouped;
  }

  /**
   * Находит термин по machine name.
   */
  private function findTermByMachineName($machine_name) {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'docpages')
      ->condition('field_code', $machine_name)
      ->range(0, 1)
      ->accessCheck(FALSE);

    $tids = $query->execute();

    if (!empty($tids)) {
      $tid = reset($tids);
      return Term::load($tid);
    }

    return NULL;
  }
}
