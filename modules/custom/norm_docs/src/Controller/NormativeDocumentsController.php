<?php

namespace Drupal\norm_docs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;

class NormativeDocumentsController extends ControllerBase {

  public function page() {

    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('document_categories', 0, NULL, TRUE);

    $build = [
      '#theme' => 'normative_documents_page',
      '#documents_by_category' => [],
    ];

    foreach ($terms as $term) {
      // Получаем документы этой категории
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'docs')
        ->condition('field_categ', $term->id())
        ->sort('created', 'ASC')
        ->accessCheck(TRUE);

      $nids = $query->execute();

      if (!empty($nids)) {
        $build['#documents_by_category'][$term->id()] = [
          'term' => $term,
          'documents' => Node::loadMultiple($nids),
        ];
      }
    }

    $build['#attached']['library'][] = 'norm_docs/normative-documents';

    return $build;

  }

  /**
   * Ищет поле типа file или entity_reference:file в ноде.
   */
  /*private function findFileField(Node $node) {
    $field_definitions = $node->getFieldDefinitions();

    foreach ($field_definitions as $field_name => $definition) {
      // Проверяем поле типа file
      if ($definition->getType() === 'file') {
        return $field_name;
      }

      // Или entity_reference на file
      if ($definition->getType() === 'entity_reference') {
        $settings = $definition->getSettings();
        if (isset($settings['target_type']) && $settings['target_type'] === 'file') {
          return $field_name;
        }
      }
    }

    return null;
  }*/
}
