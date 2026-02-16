<?php

namespace Drupal\budget_execution;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of budget execution entities.
 *
 * @ingroup budget_execution
 */
class BudgetExecutionListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Количество записей на странице.
   *
   * @var int
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->formBuilder = $container->get('form_builder');
    $instance->requestStack = $container->get('request_stack');
    $instance->storage = $container->get('entity_type.manager')->getStorage($entity_type->id());
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'budget_execution_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();

    $form['#method'] = 'get';
    $form['#attributes']['class'][] = 'budget-execution-filter-form';

    // Поле фильтрации по типу данных
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Тип данных'),
      '#options' => [
        '' => $this->t('- Любой -'),
        'income' => $this->t('Доходы'),
        'expense_sector' => $this->t('Расходы по отраслям'),
        'expense_program' => $this->t('Расходы по программам'),
        'invest' => $this->t('Бюджетные инвестиции'),
        'source' => $this->t('Источники'),
      ],
      '#default_value' => $request->query->get('type', ''),
    ];

    // Поле фильтрации по году
    $form['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Год'),
      '#options' => $this->getYearOptions(),
      '#default_value' => $request->query->get('year', ''),
    ];

    // Поле фильтрации по месяцу
    $form['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Месяц'),
      '#options' => $this->getMonthOptions(),
      '#default_value' => $request->query->get('month', ''),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Фильтровать'),
    ];

    $form['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Сбросить'),
      '#url' => Url::fromRoute('entity.budget_execution.collection'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->requestStack->getCurrentRequest()->query;

    // Сохраняем значения фильтров в query параметры
    $filters = ['type', 'year', 'month'];
    foreach ($filters as $filter) {
      $value = $form_state->getValue($filter);
      if (!empty($value)) {
        $query->set($filter, $value);
      } else {
        $query->remove($filter);
      }
    }

    // Перенаправляем на ту же страницу с параметрами фильтрации
    $url = Url::fromRoute('entity.budget_execution.collection', [], [
      'query' => $query->all(),
    ]);

    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Никакой валидации не требуется
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    // Добавляем форму фильтрации перед таблицей
    $build['filter_form'] = $this->formBuilder->getForm($this);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $request = $this->requestStack->getCurrentRequest();
    $query = $this->storage->getQuery()
      ->accessCheck(TRUE);

    // Добавляем фильтры к запросу
    $type = $request->query->get('type');
    if (!empty($type)) {
      $query->condition('type', $type);
    }

    $year = $request->query->get('year');
    if (!empty($year)) {
      $query->condition('year', (int) $year);
    }

    $month = $request->query->get('month');
    if (!empty($month)) {
      $query->condition('month', (int) $month);
    }

    // Получаем заголовки для сортировки
    $header = $this->buildHeader();
    $query->tableSort($header);

    // Добавляем пагинацию
    $query->pager($this->limit);

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => [
        'data' => $this->t('ID'),
        'field' => 'id',
        'specifier' => 'id',
      ],
      'type' => [
        'data' => $this->t('Тип данных'),
        'field' => 'type',
        'specifier' => 'type',
      ],
      'category_code' => [
        'data' => $this->t('Код'),
        'field' => 'category_code',
        'specifier' => 'category_code',
      ],
      'category_name' => [
        'data' => $this->t('Наименование'),
        'field' => 'category_name',
        'specifier' => 'category_name',
      ],
      'date' => [
        'data' => $this->t('Дата отчёта'),
        'field' => 'date',
        'specifier' => 'date',
      ],
      'plan_value' => [
        'data' => $this->t('Плановое значение'),
        'field' => 'plan_value',
        'specifier' => 'plan_value',
      ],
      'actual_value' => [
        'data' => $this->t('Фактическое значение'),
        'field' => 'actual_value',
        'specifier' => 'actual_value',
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\budget_execution\Entity\BudgetExecution $entity */

    // Маппинг типов для отображения
    $type_map = [
      'income' => $this->t('Доходы'),
      'expense_sector' => $this->t('Расходы по отраслям'),
      'expense_program' => $this->t('Расходы по программам'),
      'invest' => $this->t('Бюджетные инвестиции'),
      'source' => $this->t('Источники'),
    ];

    $row = [
      'id' => $entity->id(),
      'type' => $type_map[$entity->get('type')->value] ?? $entity->get('type')->value,
      'category_code' => $entity->get('category_code')->value,
      'category_name' => [
        'data' => [
          '#type' => 'link',
          '#title' => $entity->get('category_name')->value,
          '#url' => $entity->toUrl(),
        ],
      ],
      'date' => $entity->get('date')->value,
      'plan_value' => number_format($entity->get('plan_value')->value, 0, '', ' '),
      'actual_value' => number_format($entity->get('actual_value')->value, 0, '', ' '),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * Получить список доступных годов для фильтрации.
   */
  protected function getYearOptions() {
    $years = ['' => $this->t('- Любой -')];

    try {
      // Используем прямой запрос к базе данных для получения уникальных годов
      $database = \Drupal::database();
      $result = $database->select('budget_execution_base', 'b')
        ->fields('b', ['year'])
        ->condition('year', 0, '>')
        ->groupBy('year')
        ->orderBy('year', 'DESC')
        ->execute();

      foreach ($result as $record) {
        $year = $record->year;
        if ($year) {
          $years[$year] = $year;
        }
      }
    } catch (\Exception $e) {
      // Если что-то пошло не так, добавляем текущий год
      $years[date('Y')] = date('Y');
    }

    return $years;
  }

  /**
   * Получить список месяцев для фильтрации.
   */
  protected function getMonthOptions() {
    return [
      '' => $this->t('- Любой -'),
      '1' => $this->t('Январь'),
      '2' => $this->t('Февраль'),
      '3' => $this->t('Март'),
      '4' => $this->t('Апрель'),
      '5' => $this->t('Май'),
      '6' => $this->t('Июнь'),
      '7' => $this->t('Июль'),
      '8' => $this->t('Август'),
      '9' => $this->t('Сентябрь'),
      '10' => $this->t('Октябрь'),
      '11' => $this->t('Ноябрь'),
      '12' => $this->t('Декабрь'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit() {
    return $this->limit;
  }

}
