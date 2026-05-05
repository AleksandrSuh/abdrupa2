<?php

namespace Drupal\maillog;

use Drupal\Core\Database\Connection;

/**
 * Log cleaner.
 */
class MailLogCleaner {

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Add the dependencies.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Clear the mail logs from database.
   */
  public function clearMaillogs(string $type, int $limit) {
    $deleted = 0;
    switch ($type) {
      case 'time_to_keep':
        $time_limit = strtotime('-' . ($limit + 1) . 'days');
        $deleted = $this->database->delete('maillog')
          ->condition('sent_date', $time_limit, '<')
          ->execute();

        break;

      case 'number_to_keep':
        $min_row = $this->database->select('maillog', 'm')
          ->fields('m', ['id'])
          ->orderBy('id', 'DESC')
          ->range($limit - 1, 1)
          ->execute()->fetchField();
        if ($min_row) {
          $deleted = $this->database->delete('maillog')
            ->condition('id', $min_row, '<')
            ->execute();
        }
        break;
    }
    return $deleted;
  }

}
