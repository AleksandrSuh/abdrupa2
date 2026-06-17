<?php

namespace Drupal\budget\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CityFinanceForm extends FormBase {

  public function getFormId() {
    return 'budget_city_finance_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $year = $form_state->getValue('year') ?: date('Y');

    $curr_y = date('Y');
    $years = range($curr_y - 3, $curr_y);

    $form['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Year'),
      '#options' => array_combine($years, $years),
      '#default_value' => $year,
      '#ajax' => [
        'callback' => '::updateTable',
        'wrapper' => 'finance-table-wrapper',
        'event' => 'change',
      ],
      '#attributes' => [
        'style' => 'width: 150px; margin: 0 auto;',
      ],
    ];

    // Загружаем данные для выбранного года
    $storage = \Drupal::entityTypeManager()->getStorage('city_finance');
    $entities = $storage->loadByProperties(['year' => $year]);

    $data_by_city = [];
    foreach ($entities as $entity) {
      $city = $entity->get('city')->value;
      $data_by_city[$city] = [
        'income' => $entity->get('income')->value,
        'expense' => $entity->get('expense')->value,
      ];
    }

    $cities = ['Екатеринбург', 'Ростов-на-Дону', 'Пермь', 'Казань', 'Нижний Новгород', 'Челябинск', 'Новосибирск'];

    $form['table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'finance-table-wrapper'],
    ];

    $form['table_wrapper']['data'] = [
      '#type' => 'table',
      '#header' => ['Город', 'Доходы', 'Расходы'],
      '#tree' => TRUE,
      '#attributes' => [
        'class' => ['city-finance-table'],
        'style' => 'width: 600px; margin: 0 auto;',
      ],
    ];

    foreach ($cities as $city) {
      $form['table_wrapper']['data'][$city]['city'] = [
        '#type' => 'markup',
        '#markup' => $city,
      ];

      $summ_inc = $summ_exp = 0;
      if (isset($data_by_city[$city]))
      {
        $summ_inc = intval($data_by_city[$city]['income']);
        $summ_exp = intval($data_by_city[$city]['expense']);
      }

      // ЯВНО устанавливаем значения через #value
      $form['table_wrapper']['data'][$city]['income'] = [
        '#type' => 'number',
        '#size' => 15,
        '#value' => $summ_inc,
      ];

      $form['table_wrapper']['data'][$city]['expense'] = [
        '#type' => 'number',
        '#size' => 15,
        '#value' => $summ_exp,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Сохранить все'),
    ];

    $form['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('🗑 Удалить данные за этот год'),
      '#url' => Url::fromRoute('budget.finance_delete', ['year' => $year]),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
    ];

    return $form;
  }

  public function updateTable(array $form, FormStateInterface $form_state) {
    $year = $form_state->getValue('year');
    //\Drupal::logger('budget')->notice('AJAX update for year: @year', ['@year' => $year]);

    // Возвращаем обновлённую таблицу
    return $form['table_wrapper'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $year = $form_state->getValue('year');
    $data = $form_state->getValue('data');

    $storage = \Drupal::entityTypeManager()->getStorage('city_finance');

    foreach ($data as $city => $values) {
      $entities = $storage->loadByProperties([
        'city' => $city,
        'year' => $year,
      ]);
      $entity = reset($entities);

      if (!$entity) {
        $entity = $storage->create([
          'city' => $city,
          'year' => $year,
        ]);
      }

      $entity->set('income', $values['income'] ?: 0);
      $entity->set('expense', $values['expense'] ?: 0);
      $entity->save();
    }

    $this->messenger()->addMessage($this->t('Данные для @year успешно сохранены.', ['@year' => $year]));
    $form_state->setRebuild(TRUE);
  }
}
