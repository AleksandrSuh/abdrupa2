<?php

namespace Drupal\budget_execution;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the budget execution entity.
 */
class BudgetExecutionListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new BudgetExecutionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'type' => $this->t('Тип'),
      'category_code' => $this->t('Код'),
      'category_name' => $this->t('Наименование'),
      'date' => $this->t('Дата'),
      'plan_value' => $this->t('План'),
      'actual_value' => $this->t('Факт'),
      'created' => $this->t('Создано'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\budget_execution\Entity\BudgetExecution $entity */

    // Форматируем значения
    $type_labels = [
      'income' => 'Доходы',
      'expense_sector' => 'Расходы по отраслям',
      'expense_program' => 'Расходы по программам',
      'invest' => 'Бюджетные инвестиции',
      'source' => 'Источники',
    ];

    $type = isset($type_labels[$entity->get('type')->value])
      ? $type_labels[$entity->get('type')->value]
      : $entity->get('type')->value;

    // Форматируем дату из YYYY-MM-DD в DD.MM.YYYY
    $date_value = $entity->get('date')->value;
    $formatted_date = $date_value;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_value, $matches)) {
      $formatted_date = $matches[3] . '.' . $matches[2] . '.' . $matches[1];
    }

    $row = [
      'type' => $type,
      'category_code' => $entity->get('category_code')->value,
      'category_name' => $entity->get('category_name')->value,
      'date' => $formatted_date,
      'plan_value' => number_format($entity->get('plan_value')->value, 0, ',', ' '),
      'actual_value' => number_format($entity->get('actual_value')->value, 0, ',', ' '),
      'created' => $this->dateFormatter->format($entity->get('created')->value, 'short'),
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('Нет данных об исполнении бюджета.');
    return $build;
  }

}
