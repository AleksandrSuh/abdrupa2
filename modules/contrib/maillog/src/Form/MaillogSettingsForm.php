<?php

namespace Drupal\maillog\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure file system settings for this site.
 */
class MaillogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maillog_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['maillog.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('maillog.settings');

    $form = [];

    $form['clear_maillog'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Clear Maillog'),
    ];

    $form['clear_maillog']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all maillog entries'),
      '#submit' => ['::clearLog'],
    ];

    $form['maillog_send'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the e-mails to be delivered.'),
      '#default_value' => $config->get('send'),
    ];

    $form['maillog_nosend_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify non-admin users that delivery is disabled?'),
      '#description' => $this->t('Normally if the visitor does not have the "Administer Maillog" permission they will not know if email delivery is disabled because it includes a link to this settings page; enabling this option will display a message for visitosr who do not have this permission.'),
      '#default_value' => $config->get('nosend_notify') ?? FALSE,
    ];

    $form['maillog_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create table entries in maillog table for each e-mail.'),
      '#default_value' => $config->get('log'),
    ];

    $form['maillog_log_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify visitors that email was logged?'),
      '#default_value' => $config->get('log_notify') ?? FALSE,
    ];

    $form['maillog_verbose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the e-mails on page.'),
      '#default_value' => $config->get('verbose'),
      '#description' => $this->t('If enabled, anonymous users with permissions will see any verbose output mail.'),
    ];

    $form['maillog_body_trimmed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Body trimmed'),
      '#description' => $this->t('Since mail bodies can be large, storing the whole mail body can bloat your database. <br> Check it to store a short body (just the first 512 characters).'),
      '#default_value' => $config->get('body_trimmed'),
    ];

    $form['maillog_base64'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove base64 from body'),
      '#default_value' => $config->get('base64_remove'),
      '#description' => $this->t('If enabled, all base64 will be deleted from the body.'),
    ];

    $keep_options = $config->get('keep_limit_type');

    $form['cron'] = [
      '#type' => 'fieldset',
    ];

    $form['cron']['cron_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cron'),
      '#default_value' => $config->get('cron_enabled'),
    ];

    $form['cron']['keep_limit_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Keep maillog options'),
      '#default_value' => $keep_options,
      '#states' => [
        'visible' => [
          'input[name="cron_enabled"]' => ['checked' => TRUE],
        ],
        'required' => [
          'input[name="cron_enabled"]' => ['checked' => TRUE],
        ],
      ],
      '#options' => [
        'number_to_keep' => $this->t('Number of mail logs to keep'),
        'time_to_keep' => $this->t('Time limit to keep mail logs'),
      ],
    ];

    $form['cron']['number_to_keep'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of mail logs to keep.'),
      '#default_value' => $config->get('number_to_keep'),
      '#states' => [
        'visible' => [
          'input[name="cron_enabled"]' => ['checked' => TRUE],
          'select[name="keep_limit_type"]' => ['value' => 'number_to_keep'],
        ],
        'required' => [
          'input[name="cron_enabled"]' => ['checked' => TRUE],
          'select[name="keep_limit_type"]' => ['value' => 'number_to_keep'],
        ],
      ],
    ];

    $form['cron']['time_to_keep'] = [
      '#type' => 'number',
      '#title' => $this->t('Time to keep the mail logs in days.'),
      '#default_value' => $config->get('time_to_keep'),
      '#states' => [
        'visible' => [
          'input[name="cron_enabled"]' => ['checked' => TRUE],
          'select[name="keep_limit_type"]' => ['value' => 'time_to_keep'],
        ],
        'required' => [
          'input[name="cron_enabled"]' => ['checked' => TRUE],
          'select[name="keep_limit_type"]' => ['value' => 'time_to_keep'],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('maillog.settings')
      ->set('send', $form_state->getValue('maillog_send'))
      ->set('nosend_notify', $form_state->getValue('maillog_nosend_notify'))
      ->set('log', $form_state->getValue('maillog_log'))
      ->set('log_notify', $form_state->getValue('maillog_log_notify'))
      ->set('verbose', $form_state->getValue('maillog_verbose'))
      ->set('body_trimmed', $form_state->getValue('maillog_body_trimmed'))
      ->set('base64_remove', $form_state->getValue('maillog_base64'))
      ->set('cron_enabled', $form_state->getValue('cron_enabled'))
      ->set('keep_limit_type', $form_state->getValue('keep_limit_type'))
      ->set('number_to_keep', $form_state->getValue('number_to_keep'))
      ->set('time_to_keep', $form_state->getValue('time_to_keep'))
      ->save();

    parent::submitForm($form, $form_state);

    if ($this->config('maillog.settings')->get('verbose') == TRUE) {
      $this->messenger()->addWarning($this->t('Any user having the permission "view maillog" will see output of any mail that is sent.'));
    }
  }

  /**
   * Clear all the maillog entries.
   */
  public function clearLog(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('maillog.clear_log');
  }

}
