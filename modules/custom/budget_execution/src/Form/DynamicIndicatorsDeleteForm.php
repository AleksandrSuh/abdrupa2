<?php

namespace Drupal\budget_execution\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Подтверждение удаления данных «динамики» за конкретный год.
 *
 * Удаляет все записи за год из таблицы budget_execution_indicators
 * (и доходы, и расходы). Удаление выполняется по POST, а не по ссылке,
 * чтобы данные нельзя было снести случайным переходом или предзагрузкой.
 */
class DynamicIndicatorsDeleteForm extends ConfirmFormBase {

  /**
   * Удаляемый год.
   *
   * @var int|null
   */
  protected $year;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'budget_execution_dynamic_indicators_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $year = NULL) {
    $this->year = (int) $year;
    $form = parent::buildForm($form, $form_state);
    // Оформляем кнопку подтверждения как «опасное» действие.
    $form['actions']['submit']['#button_type'] = 'danger';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Удалить все данные за @year год?', ['@year' => $this->year]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $counts = \Drupal::database()->query(
      'SELECT type, COUNT(*) AS cnt FROM {budget_execution_indicators}
       WHERE year = :year GROUP BY type',
      [':year' => $this->year]
    )->fetchAllKeyed();

    return $this->t(
      'Будут удалены все записи за @year год: доходы — @income, расходы — @expense. Действие нельзя отменить.',
      [
        '@year' => $this->year,
        '@income' => (int) ($counts['income'] ?? 0),
        '@expense' => (int) ($counts['expense'] ?? 0),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Удалить');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Отмена');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('budget_execution.dynamic_indicators');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $deleted = \Drupal::database()->delete('budget_execution_indicators')
      ->condition('year', $this->year)
      ->execute();

    $this->messenger()->addStatus($this->t('За @year год удалено записей: @count.', [
      '@year' => $this->year,
      '@count' => $deleted,
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
