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
        'invest' => t('Бюджетные инвестиции'),
        'source' => t('Источники'),
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

    $fields['date'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Дата отчёта'))
      ->setDescription(t('Дата отчёта в формате ДД.ММ.ГГГГ (31 декабря или 1-е числа месяцев кроме января)'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 10)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
        'settings' => [
          'size' => 10,
          'placeholder' => 'ДД.ММ.ГГГГ',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',  // Простой строковый formatter
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Plan value
    $fields['plan_value'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Плановое значение'))
      ->setDescription(t('Плановое значение бюджета'))
      ->setRequired(TRUE)
      ->setSetting('size', 'big')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Actual value
    $fields['actual_value'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Фактическое значение'))
      ->setDescription(t('Фактическое значение бюджета'))
      ->setRequired(TRUE)
      ->setSetting('size', 'big')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -5,
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



  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Автоматически заполняем год и месяц из даты
    $date_value = $this->get('date')->value;
    if (is_string($date_value) && preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $date_value, $matches)) {
      $this->set('year', (int) $matches[3]);
      $this->set('month', (int) $matches[2]);
    }

    // Для отладки можно добавить логирование
    // \Drupal::logger('budget_execution')->debug('preSave: date=@date, year=@year, month=@month', [
    //   '@date' => $date_value,
    //   '@year' => $this->get('year')->value,
    //   '@month' => $this->get('month')->value,
    // ]);
  }
}
