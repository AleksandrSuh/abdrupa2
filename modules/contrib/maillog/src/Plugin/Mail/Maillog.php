<?php

namespace Drupal\maillog\Plugin\Mail;

use Drupal\Core\Database\Database;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a 'Dummy' plugin to send emails.
 *
 * @Mail(
 *   id = "maillog",
 *   label = @Translation("Maillog Mail-Plugin"),
 *   description = @Translation("Maillog mail plugin for sending and formating complete mails.")
 * )
 */
class Maillog implements MailInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    $default = new PhpMail();
    return $default->format($message);
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $config = \Drupal::configFactory()->get('maillog.settings');
    // Log the e-mail.
    if ($config->get('log')) {
      $record = new \stdClass();

      // In case the subject/from/to is already encoded, decode with
      // iconv_mime_decode().
      $record->header_message_id = $message['headers']['Message-ID'] ?? $this->t('Not delivered');
      $subject = $message['subject'];
      if ($this->isMIMEEncoded($subject)) {
        $subject = iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR);
      }
      $record->subject = mb_substr($subject, 0, 255);
      $record->header_from = $message['from'] ?? NULL;
      $record->header_from = iconv_mime_decode($record->header_from);

      // Compile the body text for the log.
      $body = $message['body'];
      if ($config->get('base64_remove')) {
        $re = '/"data:[\w]+\/[\w]+;base64,.*"/m';
        $str_no_brl = str_replace(PHP_EOL, '', $body);
        $body = preg_replace($re, '"removed by maillog"', $str_no_brl);
      }
      if ($config->get('body_trimmed')) {
        $body = mb_substr($body, 0, 512) . '...trimmed';
      }
      $record->body = $body;

      $header_to = [];
      if (isset($message['to'])) {
        if (is_array($message['to'])) {
          foreach ($message['to'] as $value) {
            $header_to[] = iconv_mime_decode($value);
          }
        }
        else {
          $header_to[] = iconv_mime_decode($message['to']);
        }
      }
      $record->header_to = implode(', ', $header_to);

      $record->header_reply_to = $message['headers']['Reply-To'] ?? '';
      $record->header_all = serialize($message['headers']);
      $record->sent_date = \Drupal::time()->getRequestTime();

      Database::getConnection()->insert('maillog')
        ->fields((array) $record)
        ->execute();

      // If the "log notify" option is enabled, inform the visitor that the
      // email was logged.
      if ($config->get('log_notify')) {
        \Drupal::messenger()->addStatus($this->t('An email was logged.'), TRUE);
      }
    }

    // Display the email if the verbose is enabled.
    if ($config->get('verbose') && \Drupal::currentUser()->hasPermission('view maillog')) {
      // Print the message.
      $header_output = print_r($message['headers'], TRUE);
      $output = $this->t('A mail has been sent: <br/> [Subject] => @subject <br/> [From] => @from <br/> [To] => @to <br/> [Reply-To] => @reply <br/> <pre>  [Header] => @header <br/> [Body] => @body </pre>', [
        '@subject' => $message['subject'],
        '@from' => $message['from'],
        '@to' => $message['to'],
        '@reply' => $message['reply_to'] ?? '',
        '@header' => $header_output,
        '@body' => $message['body'],
      ]);
      \Drupal::messenger()->addStatus($output, TRUE);
    }

    if ($config->get('send')) {
      $default = new PhpMail();
      $result = $default->mail($message);
    }
    elseif (\Drupal::currentUser()->hasPermission('administer maillog')) {
      $message = $this->t('Sending of e-mail messages is disabled by Maillog module. Go @here to enable.', ['@here' => \Drupal::service('link_generator')->generate('here', Url::fromRoute('maillog.settings'))]);

      \Drupal::messenger()->addWarning($message, TRUE);
    }
    elseif ($config->get('nosend_notify')) {
      \Drupal::messenger()->addStatus($this->t('Email delivery is currently disabled and the site attempted to deliver an email.'), TRUE);
    }
    else {
      \Drupal::logger('maillog')->notice('Attempted to send an email, but sending emails is disabled.');
    }
    return $result ?? TRUE;
  }

  /**
   * Determine if a given string is MIME encoded.
   *
   * @param string $string
   *   The string to check.
   *
   * @return bool
   *   Whether the string is MIME encoded.
   */
  protected function isMIMEEncoded($string) {
    return preg_match('/^=\?.+\?.\?.+\?=$/u', $string) === 1;
  }

}
