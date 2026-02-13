<?php

namespace Drupal\budget_execution\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DynamicIndicatorsForm extends FormBase {

  /**
   * Массив категорий в зависимости от типа
   */
  protected $categories = [
    'income' => [
      'Налоговые и неналоговые доходы (млн руб.)' => 'Налоговые и неналоговые доходы (млн руб.)',
      'Объем безвозмездных поступлений (млн руб.)' => 'Объем безвозмездных поступлений (млн руб.)',
    ],
    'expense' => [
      'Объем расходов местного бюджета (млн руб.)' => 'Объем расходов местного бюджета (млн руб.)',
      'Объем межбюджетных трансфертов (млн руб.)' => 'Объем межбюджетных трансфертов (млн руб.)',
    ],
  ];

  public function getFormId() {
    return 'budget_execution_dynamic_indicators_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    // Получаем текущий выбранный тип из form_state
    $selected_type = $form_state->getValue('type', 'income');

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Тип показателя'),
      '#options' => [
        'income' => $this->t('Доход'),
        'expense' => $this->t('Расход'),
      ],
      '#required' => TRUE,
      '#default_value' => $selected_type,
      '#ajax' => [
        'callback' => '::ajaxUpdateCategories',
        'wrapper' => 'edit-category-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Загрузка категорий...'),
        ],
      ],
    ];

    // Контейнер с фиксированным ID, который совпадает с wrapper
    $form['category_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'edit-category-wrapper'],
    ];

    // Получаем категории для выбранного типа
    $categories = $this->categories[$selected_type];

    $form['category_wrapper']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Категория'),
      '#options' => $categories,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Выберите категорию -'),
      '#default_value' => $form_state->getValue('category'),
    ];

    $form['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Значение (млн руб.)'),
      '#required' => TRUE,
      '#step' => 0.01,
      '#min' => 0,
    ];

    $form['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Год'),
      '#required' => TRUE,
      '#options' => $this->getYearOptions(),
      '#default_value' => date('Y'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Сохранить'),
    ];

    return $form;
  }

  /**
   * AJAX callback для обновления категорий.
   */
  public function ajaxUpdateCategories(array &$form, FormStateInterface $form_state) {
    return $form['category_wrapper'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('type');
    $category = $form_state->getValue('category');

    if (empty($category)) {
      $form_state->setErrorByName('category', $this->t('Выберите категорию.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = \Drupal::database();

    $fields = [
      'uuid' => \Drupal::service('uuid')->generate(),
      'type' => $form_state->getValue('type'),
      'category' => $form_state->getValue('category'),
      'value' => $form_state->getValue('value'),
      'year' => $form_state->getValue('year'),
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
    ];

    $connection->insert('budget_execution_indicators')
      ->fields($fields)
      ->execute();

    $this->messenger()->addStatus($this->t('Показатель добавлен.'));

    // Сбрасываем поле значения
    $form_state->setRebuild(TRUE);
    $form_state->setValue('value', '');
    $form_state->setValue('category', '');
  }

  protected function getYearOptions() {
    $current_year = (int) date('Y');
    $years = [];
    for ($year = $current_year - 6; $year <= $current_year; $year++) {
      $years[$year] = $year;
    }
    return $years;
  }

}
