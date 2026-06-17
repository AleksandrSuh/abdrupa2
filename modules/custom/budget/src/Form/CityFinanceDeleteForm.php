<?php

namespace Drupal\budget\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CityFinanceDeleteForm extends ConfirmFormBase {

  protected $year;

  public function getFormId() {
    return 'budget_finance_delete_form';
  }

  public function getQuestion() {
    return $this->t('Удалить все данные для @year?', ['@year' => $this->year]);
  }

  public function getCancelUrl() {
    return new Url('budget.finance_add');
  }

  public function buildForm(array $form, FormStateInterface $form_state, $year = NULL) {
    $this->year = $year;
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = \Drupal::entityTypeManager()->getStorage('city_finance');
    $entities = $storage->loadByProperties(['year' => $this->year]);
    $storage->delete($entities);

    $this->messenger()->addMessage($this->t('Все данные для @year стёрты.', ['@year' => $this->year]));
    $form_state->setRedirect('budget.finance_add');
  }
}
