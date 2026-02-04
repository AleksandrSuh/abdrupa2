<?php

namespace Drupal\budget_execution\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Budget Execution entity.
 *
 * @ContentEntityType(
 *   id = "budget_execution",
 *   label = @Translation("Budget Execution"),
 *   label_collection = @Translation("Budget Executions"),
 *   label_singular = @Translation("budget execution"),
 *   label_plural = @Translation("budget executions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count budget execution",
 *     plural = "@count budget executions",
 *   ),
 *   base_table = "budget_execution_base",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "category_name",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\budget_execution\BudgetExecutionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\budget_execution\Form\BudgetExecutionForm",
 *       "add" = "Drupal\budget_execution\Form\BudgetExecutionForm",
 *       "edit" = "Drupal\budget_execution\Form\BudgetExecutionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\budget_execution\BudgetExecutionAccessControlHandler",
 *   },
 *   admin_permission = "administer budget execution",
 *   links = {
 *     "canonical" = "/admin/budget/execution/{budget_execution}",
 *     "add-form" = "/admin/budget/execution/add",
 *     "edit-form" = "/admin/budget/execution/{budget_execution}/edit",
 *     "delete-form" = "/admin/budget/execution/{budget_execution}/delete",
 *     "collection" = "/admin/budget/execution",
 *   },
 *   field_ui_base_route = "entity.budget_execution.collection",
 * )
 */
class BudgetExecution extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Type field - выбор типа данных
    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Тип данных'))
      ->setDescription(t('Выберите тип данных бюджета'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'income' => t('Доходы'),
        'expense_sector' => t('Расходы по отраслям'),
        'expense_program' => t('Расходы по программам'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Category/Sector/Program code
    $fields['category_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Код'))
      ->setDescription(t('Код категории/отрасли/программы'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Category/Sector/Program name
    $fields['category_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Наименование'))
      ->setDescription(t('Наименование категории/отрасли/программы'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Дата отчёта'))
      ->setDescription(t('Дата отчёта (1-е число месяца или 31 декабря)'))
      ->setRequired(TRUE)
      ->setSettings([
        'datetime_type' => 'date', // ТОЛЬКО ДАТА, без времени
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -7,
        'settings' => [
          'format_type' => 'html_date', // Только дата в форме
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -7,
        'settings' => [
          'format_type' => 'medium',
          'timezone_override' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Plan value
    $fields['plan_value'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Плановое значение'))
      ->setDescription(t('Плановое значение бюджета'))
      ->setRequired(TRUE)
      ->setSettings([
        'min' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -6,
        'settings' => [
          'step' => '0.01',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    /*$fields['plan_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Плановое значение'))
      ->setDescription(t('Плановое значение бюджета'))
      ->setRequired(TRUE)
      ->setSetting('precision', 20)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -6,
        'settings' => [
          'thousand_separator' => ' ',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -6,
        'settings' => [
          'thousand_separator' => ' ',
          'scale' => 0,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);*/

    // Actual value
    $fields['actual_value'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Фактическое значение'))
      ->setDescription(t('Фактическое значение бюджета'))
      ->setRequired(TRUE)
      ->setSettings([
        'min' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -5,
        'settings' => [
          'step' => '1',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Created timestamp
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Создано'))
      ->setDescription(t('Время создания записи'))
      ->setDisplayConfigurable('view', TRUE);

    // Changed timestamp
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Изменено'))
      ->setDescription(t('Время последнего изменения записи'))
      ->setDisplayConfigurable('view', TRUE);

    // User ID
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Автор'))
      ->setDescription(t('Пользователь, создавший запись'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\budget_execution\Entity\BudgetExecution::getCurrentUserId')
      ->setDisplayConfigurable('view', TRUE);

    // Generated year field (для фильтрации)
    $fields['year'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Год'))
      ->setDescription(t('Год, извлечённый из даты'))
      ->setDisplayConfigurable('view', TRUE);

    // Generated month field (для фильтрации)
    $fields['month'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Месяц'))
      ->setDescription(t('Месяц, извлечённый из даты'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);

    foreach ($entities as $entity) {
      // Исправляем поле date при загрузке
      $date_value = $entity->get('date')->value;

      \Drupal::logger('budget_execution')->debug('postLoad - Date: @value (type: @type)', [
        '@value' => $date_value,
        '@type' => gettype($date_value)
      ]);

      // Если это строка даты - создаём DrupalDateTime для отображения
      if (is_string($date_value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_value)) {
        // Создаём DrupalDateTime из строки
        try {
          $drupal_date = \Drupal\Core\Datetime\DrupalDateTime::createFromFormat(
            'Y-m-d',
            $date_value,
            new \DateTimeZone('UTC')
          );

          if ($drupal_date) {
            $entity->set('date', $drupal_date);
          }
        } catch (\Exception $e) {
          \Drupal::logger('budget_execution')->error('Error creating DrupalDateTime: @error', [
            '@error' => $e->getMessage()
          ]);
        }
      }
      // Если timestamp - тоже создаём DrupalDateTime
      elseif (is_numeric($date_value)) {
        try {
          $drupal_date = \Drupal\Core\Datetime\DrupalDateTime::createFromTimestamp(
            (int) $date_value,
            new \DateTimeZone('UTC')
          );

          if ($drupal_date) {
            $entity->set('date', $drupal_date);
          }
        } catch (\Exception $e) {
          \Drupal::logger('budget_execution')->error('Error creating DrupalDateTime from timestamp: @error', [
            '@error' => $e->getMessage()
          ]);
        }
      }
    }
  }
  public function preSave(EntityStorageInterface $storage)
  {
    parent::preSave($storage);

    // ТРАССИРОВКА ДАТЫ
    $date_value = $this->get('date')->value;
    \Drupal::logger('budget_execution_Здб')->debug('preSave START - Date value: @value (type: @type)', [
      '@value' => $date_value,
      '@type' => gettype($date_value)
    ]);

    // Преобразование даты
    $date_string = '';

    if ($date_value instanceof \Drupal\Core\Datetime\DrupalDateTime) {
      $date_string = $date_value->format('Y-m-d');
      $this->get('date')->value = $date_string;
      \Drupal::logger('budget_execution_Здб')->debug('preSave: DrupalDateTime -> @date', ['@date' => $date_string]);
    } elseif (is_string($date_value)) {
      $date_string = $date_value;
      \Drupal::logger('budget_execution_Здб')->debug('preSave: Already string @date', ['@date' => $date_string]);
    } elseif (is_numeric($date_value)) {
      $date = \DateTime::createFromFormat('U', (int)$date_value, new \DateTimeZone('UTC'));
      $date_string = $date->format('Y-m-d');
      $this->get('date')->value = $date_string;
      \Drupal::logger('budget_execution_Здб')->debug('preSave: Timestamp @ts -> @date', [
        '@ts' => $date_value,
        '@date' => $date_string
      ]);
    }

    // Заполняем год и месяц
    if ($date_string && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_string, $matches)) {
      $this->set('year', (int)$matches[1]);
      $this->set('month', (int)$matches[2]);
      \Drupal::logger('budget_execution_Здб')->debug('preSave: Set year=@year, month=@month', [
        '@year' => $matches[1],
        '@month' => $matches[2]
      ]);
    }

    \Drupal::logger('budget_execution_Здб')->debug('preSave END - Date value: @value', [
      '@value' => $this->get('date')->value
    ]);
  }
}
