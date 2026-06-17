<?php

namespace Drupal\budget\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * @ContentEntityType(
 *   id = "city_finance",
 *   label = @Translation("City Finance"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "form" = {
 *       "default" = "Drupal\budget\Form\CityFinanceForm",
 *       "delete" = "Drupal\budget\Form\CityFinanceDeleteForm"
 *     },
 *     "list_builder" = "Drupal\budget\CityFinanceListBuilder",
 *   },
 *   base_table = "city_finances",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */

class CityFinance extends ContentEntityBase
{

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('City'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 10]);

    $fields['year'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Year'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 20]);

    $fields['income'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Income'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 30]);

    $fields['expense'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Expense'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 40]);

    return $fields;
  }

}
