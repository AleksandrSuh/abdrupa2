<?php

namespace Drupal\Tests\maillog\Functional;

use Drupal\maillog\Plugin\Mail\Maillog;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the maillog clean.
 *
 * @group maillog
 */
class MailCleanTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['maillog', 'user', 'system', 'views'];

  /**
   * Define the default theme used for all tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use the maillog mail plugin.
    $this->config('system.mail')->set('interface.default', 'maillog')->save();
    // The system.site.mail setting goes into the From header of outgoing mails.
    $this->config('system.site')->set('mail', 'simpletest@example.com')->save();

    // Disable e-mail sending.
    $this->config('maillog.settings')
      ->set('send', FALSE)
      ->save();
  }

  /**
   * Test body trimmed.
   */
  public function testBodyTrimmed() {

    $this->config('maillog.settings')
      ->set('body_trimmed', TRUE)
      ->save();
    $mail = \Drupal::service('plugin.manager.mail')->mail('maillog', 'ui_test', "test+trimmed@example.com", \Drupal::languageManager()->getCurrentLanguage(), [], 'me@example.com', FALSE);
    $mail['subject'] = 'This is a test subject.';
    $mail['body'] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed magna mauris, varius ut arcu vitae, varius cursus lorem. Curabitur consequat neque lorem. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Cras id elit dui. Donec lobortis ante non risus tempor, in vehicula velit molestie. Maecenas egestas ornare arcu, eu euismod ipsum elementum et. Pellentesque tempor nibh lorem, non pellentesque ante mollis sit amet. Maecenas ullamcorper viverra luctus. Aliquam iaculis, libero sed congue blandit, justo nulla pulvinar orci, vitae tempus velit justo consectetur velit. Fusce convallis augue sem, at egestas neque consequat accumsan. Suspendisse aliquam tristique metus, non blandit orci porttitor quis. Nullam vitae arcu erat. Nam eu pretium felis, at mollis tellus. Donec vel ex et velit hendrerit malesuada sit amet ut leo. Donec vel imperdiet nisl, vitae viverra turpis. Nam dignissim, risus at pellentesque euismod, eros metus tristique massa, eget placerat.';

    // Send the prepared email.
    $sender = new Maillog();
    $sender->mail($mail);

    // Create a user with valid permissions and go to the maillog overview page.
    $permissions = [
      'administer maillog',
      'view maillog',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet('admin/reports/maillog');
    $this->assertSession()->statusCodeEquals(200);

    // Assert some values and click the subject link.
    $this->assertSession()->pageTextContains('simpletest@example.com');
    $this->assertSession()->pageTextContains("test+trimmed@example.com");
    $this->rebuildContainer();
    $query = \Drupal::database()->select('maillog', 'm');
    $body = $query->fields('m', ['body'])
      ->execute()
      ->fetchField();
    $length_trim = 512 + strlen('...trimmed');
    $this->assertEquals($length_trim, strlen($body));
    $this->assertStringContainsString('iaculis, lib...trimmed', $body);

  }

  /**
   * Test body base 64 remove.
   */
  public function testBodyBase64() {

    $this->config('maillog.settings')
      ->set('base64_remove', TRUE)
      ->save();
    $mail = \Drupal::service('plugin.manager.mail')->mail('maillog', 'ui_test', "test+base64@example.com", \Drupal::languageManager()->getCurrentLanguage(), [], 'me@example.com', FALSE);
    $mail['subject'] = 'This is a test subject.';
    $mail['body'] = 'This message is a test <img src="data:image/jpeg;base64,SGVsbG8gV29ybGQ=" /> email body.';

    // Send the prepared email.
    $sender = new Maillog();
    $sender->mail($mail);

    // Create a user with valid permissions and go to the maillog overview page.
    $permissions = [
      'administer maillog',
      'view maillog',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet('admin/reports/maillog');
    $this->assertSession()->statusCodeEquals(200);

    // Assert some values and click the subject link.
    $this->assertSession()->pageTextContains('simpletest@example.com');
    $this->assertSession()->pageTextContains("test+base64@example.com");
    $this->rebuildContainer();
    $query = \Drupal::database()->select('maillog', 'm');
    $body = $query->fields('m', ['body'])
      ->execute()
      ->fetchField();

    $this->assertStringContainsString('This message is a test <img src="removed by maillog" /> email body.', $body);

  }

  /**
   * Test keep logs by limit number of logs.
   */
  public function testNumberToKeep() {
    $keep = 5;
    $this->config('maillog.settings')
      ->set('cron_enabled', TRUE)
      ->set('keep_limit_type', 'number_to_keep')
      ->set('number_to_keep', $keep)
      ->save();

    $limit = 10;
    $this->generateMails($limit);

    // Create a user with valid permissions and go to the maillog overview page.
    $permissions = [
      'administer maillog',
      'view maillog',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet('admin/reports/maillog');
    $this->assertSession()->statusCodeEquals(200);

    // Assert some values and click the subject link.
    $this->assertSession()->pageTextContains('simpletest@example.com');
    $count = 1;
    while ($count <= $limit) {
      $this->assertSession()->pageTextContains("test+$count@example.com");
      $count++;
    }

    // Test clear log.
    maillog_cron();
    $this->drupalGet('admin/reports/maillog');
    $this->assertSession()->statusCodeEquals(200);
    $count = 1;
    // Older test should be removed.
    while ($count <= $keep) {
      $this->assertSession()->pageTextNotContains("test+$count@example.com");
      $count++;
    }
    $count = 1;
    $limit_deleted = $limit;
    // Newer test should be kept.
    while ($count <= ($keep - $limit)) {
      $this->assertSession()->pageTextContains("test+$limit_deleted@example.com");
      $limit_deleted--;
      $count++;
    }
  }

  /**
   * Test keep logs by limit date days.
   */
  public function testDateLimitKeep() {
    $keep = 5;
    $this->config('maillog.settings')
      ->set('cron_enabled', TRUE)
      ->set('keep_limit_type', 'time_to_keep')
      ->set('time_to_keep', $keep)
      ->save();

    $limit = 10;
    $this->generateMails($limit);
    $this->updateDateMails($limit);

    // Create a user with valid permissions and go to the maillog overview page.
    $permissions = [
      'administer maillog',
      'view maillog',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet('admin/reports/maillog');
    $this->assertSession()->statusCodeEquals(200);

    // Assert some values and click the subject link.
    $this->assertSession()->pageTextContains('simpletest@example.com');
    $count = 1;
    while ($count <= $limit) {
      $this->assertSession()->pageTextContains("test+$count@example.com");
      $count++;
    }

    // Test clear log.
    maillog_cron();
    $this->drupalGet('admin/reports/maillog');
    $this->assertSession()->statusCodeEquals(200);
    $count = 1;
    // Older test should be removed.
    while ($count <= $keep) {
      $this->assertSession()->pageTextNotContains("test+$count@example.com");
      $count++;
    }
    $count = 1;
    $limit_deleted = $limit;
    // Newer test should be kept.
    while ($count <= ($limit - $keep)) {
      $this->assertSession()->pageTextContains("test+$limit_deleted@example.com");
      $limit_deleted--;
      $count++;
    }
  }

  /**
   * Generate demo emails.
   */
  private function generateMails(int $limit) {
    $count = 1;
    while ($count <= $limit) {
      $mail = \Drupal::service('plugin.manager.mail')->mail('maillog', 'ui_test', "test+$count@example.com", \Drupal::languageManager()->getCurrentLanguage(), [], 'me@example.com', FALSE);
      $mail['subject'] = 'This is a test subject.';
      $mail['body'] = 'This message is a test email body.';

      // Send the prepared email.
      $sender = new Maillog();
      $sender->mail($mail);
      $count++;
    }
  }

  /**
   * Update dates logs.
   */
  private function updateDateMails(int $limit) {
    $this->rebuildContainer();
    $query = \Drupal::database()->update('maillog');
    // It adds a date to each log on a different day based on the id.
    $query->expression('sent_date', "(UNIX_TIMESTAMP() - (86400 * ($limit + 1))) + (86400 * id)");
    $query->execute();
  }

}
