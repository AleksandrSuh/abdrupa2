<?php

namespace Drupal\budget_execution\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Form handler for the budget execution add/edit forms.
 */
class BudgetExecutionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    // Улучшаем отображение формы
    $form['category_code']['widget'][0]['value']['#title'] = $this->t('Код');
    $form['category_name']['widget'][0]['value']['#title'] = $this->t('Наименование');
    $form['plan_value']['widget'][0]['value']['#title'] = $this->t('Плановое значение');
    $form['actual_value']['widget'][0]['value']['#title'] = $this->t('Фактическое значение');

    // Добавляем валидацию даты
    $form['date']['widget'][0]['value']['#description'] = $this->t('Введите дату отчёта (только 1-е число месяца, кроме января, или 31 декабря)');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {


    \Drupal::logger('budget_execution')->debug('=== NUMERIC FIELDS DEBUG ===');

    $plan_value = $form_state->getValue('plan_value');
    $actual_value = $form_state->getValue('actual_value');

    \Drupal::logger('budget_execution')->debug('plan_value raw: @value (type: @type)', [
      '@value' => print_r($plan_value, TRUE),
      '@type' => isset($plan_value[0]['value']) ? gettype($plan_value[0]['value']) : 'NULL'
    ]);

    \Drupal::logger('budget_execution')->debug('actual_value raw: @value (type: @type)', [
      '@value' => print_r($actual_value, TRUE),
      '@type' => isset($actual_value[0]['value']) ? gettype($actual_value[0]['value']) : 'NULL'
    ]);



    $original_values = [];
    foreach (['plan_value', 'actual_value'] as $field) {
      $original_values[$field] = $form_state->getValue($field);
    }

    // Вызываем родительскую валидацию
    try {
      parent::validateForm($form, $form_state);
      \Drupal::logger('budget_execution')->debug('Parent validation passed');
    } catch (\Exception $e) {
      \Drupal::logger('budget_execution')->error('Parent validation error: @error', [
        '@error' => $e->getMessage()
      ]);
    }

    // Проверим что изменилось
    foreach (['plan_value', 'actual_value'] as $field) {
      $new_value = $form_state->getValue($field);
      if ($new_value != $original_values[$field]) {
        \Drupal::logger('budget_execution')->debug('Field @field changed by parent validation: @orig -> @new', [
          '@field' => $field,
          '@orig' => print_r($original_values[$field], TRUE),
          '@new' => print_r($new_value, TRUE)
        ]);
      }
    }


    parent::validateForm($form, $form_state);
    \Drupal::logger('budget_execution')->debug('validateForm started');

    // Логируем ВСЕ значения формы для отладки
    $values_to_log = ['type', 'plan_value', 'actual_value'];
    foreach ($values_to_log as $field) {
      $value = $form_state->getValue($field);
      \Drupal::logger('budget_execution')->debug('Field @field: @value', [
        '@field' => $field,
        '@value' => print_r($value, TRUE)
      ]);
    }

    \Drupal::logger('budget_execut_form')->debug('validateForm - Date value: @value',
      ['@value' => print_r($form_state->getValue('date'), TRUE)]);

    $date_value = $form_state->getValue('date');

    if (isset($date_value[0]['value']) && $date_value[0]['value'] instanceof \Drupal\Core\Datetime\DrupalDateTime) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $drupal_date */
      $drupal_date = $date_value[0]['value'];
      $date = $drupal_date->getPhpDateTime();

      // Проверяем допустимые даты
      $day = $date->format('d');
      $month = $date->format('m');

      $is_valid = FALSE;

      // 31 декабря
      if ($day == '31' && $month == '12') {
        $is_valid = TRUE;
      }
      // 1-е число месяца, кроме января
      elseif ($day == '01' && $month != '01') {
        $is_valid = TRUE;
      }

      if (!$is_valid) {
        $form_state->setErrorByName('date', $this->t(
          'Допустимы только следующие даты: 31 декабря или 1-е числа месяцев (кроме января). Вы ввели: @date',
          ['@date' => $date->format('d.m.Y')]
        ));
      }
    }


    // Проверка уникальности комбинации тип-код-дата
    /*$type = $form_state->getValue('type')[0]['value'];
    $category_code = $form_state->getValue('category_code')[0]['value'];
    $date = $date_value;
    $entity_id = $this->entity->id();

    if ($type && $category_code && $date) {
      $query = $this->entityTypeManager->getStorage('budget_execution')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $type)
        ->condition('category_code', $category_code)
        ->condition('date', $date);

      if ($entity_id) {
        $query->condition('id', $entity_id, '<>');
      }

      $ids = $query->execute();

      if (!empty($ids)) {
        $form_state->setErrorByName('category_code', $this->t(
          'Запись с такими значениями (Тип: @type, Код: @code, Дата: @date) уже существует.',
          [
            '@type' => $type,
            '@code' => $category_code,
            '@date' => \DateTime::createFromFormat('U', $date)->format('d.m.Y'),
          ]
        ));
      }
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    $entity_link = $entity->toLink()->toString();

    // Сообщения об успешном сохранении
    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();

    if ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Запись об исполнении бюджета создана.'));
      $this->logger('budget_execution')->notice('Создана новая запись об исполнении бюджета: %title.', [
        '%title' => $entity->label(),
        'link' => $edit_link,
      ]);
    }
    else {
      $this->messenger()->addStatus($this->t('Запись об исполнении бюджета обновлена.'));
      $this->logger('budget_execution')->notice('Обновлена запись об исполнении бюджета: %title.', [
        '%title' => $entity->label(),
        'link' => $edit_link,
      ]);
    }

    // Редирект на список
    $form_state->setRedirect('entity.budget_execution.collection');

    return $status;
  }

}
