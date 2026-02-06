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
    // Сначала вызываем стандартную валидацию
    parent::validateForm($form, $form_state);

    // 1. ВАЛИДАЦИЯ ДАТЫ
    $date_value = $form_state->getValue('date')[0]['value'] ?? '';

    if (empty($date_value)) {
      $form_state->setErrorByName('date', $this->t('Дата обязательна.'));
      return;
    }

    // Проверка формата
    if (!preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $date_value, $matches)) {
      $form_state->setErrorByName('date', $this->t('Неверный формат даты. Используйте ДД.ММ.ГГГГ'));
      return;
    }

    [$day, $month, $year] = [$matches[1], $matches[2], $matches[3]];

    // Проверка что дата существует
    if (!checkdate((int)$month, (int)$day, (int)$year)) {
      $form_state->setErrorByName('date', $this->t('Несуществующая дата.'));
      return;
    }

    // Наши бизнес-правила
    $is_valid = false;
    // 31 декабря
    if ($day == '31' && $month == '12') {
      $is_valid = true;
    }
    // 1-е число месяца, кроме января
    elseif ($day == '01' && $month != '01') {
      $is_valid = true;
    }

    if (!$is_valid) {
      $form_state->setErrorByName('date', $this->t(
        'Допустимы только: 31 декабря или 1-е числа месяцев (кроме января). Вы ввели: @date',
        ['@date' => "$day.$month.$year"]
      ));
    }

    // 2. ВАЛИДАЦИЯ ЧИСЕЛ (integer полей)
    foreach (['plan_value', 'actual_value'] as $field) {
      $value = $form_state->getValue($field)[0]['value'] ?? '';

      if ($value === '') {
        $form_state->setErrorByName($field, $this->t('Значение обязательно.'));
        continue;
      }

      // Проверяем что это целое число
      if (!is_numeric($value) || (int)$value != $value) {
        $form_state->setErrorByName($field, $this->t('Введите целое число.'));
      }

      // Преобразуем в integer на всякий случай
      $form_state->setValue($field, [['value' => (int)$value]]);
    }

    // 3. ПРОВЕРКА УНИКАЛЬНОСТИ
    $type = $form_state->getValue('type')[0]['value'] ?? '';
    $category_code = $form_state->getValue('category_code')[0]['value'] ?? '';

    if ($type && $category_code && $date_value) {
      $query = $this->entityTypeManager->getStorage('budget_execution')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $type)
        ->condition('category_code', $category_code)
        ->condition('date', $date_value);

      if ($entity_id = $this->entity->id()) {
        $query->condition('id', $entity_id, '<>');
      }

      if ($query->execute()) {
        $form_state->setErrorByName('category_code', $this->t(
          'Запись с такими значениями (Тип: @type, Код: @code, Дата: @date) уже существует.',
          [
            '@type' => $type,
            '@code' => $category_code,
            '@date' => "$day.$month.$year",
          ]
        ));
      }
    }
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
