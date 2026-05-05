<?php

namespace Drupal\maillog\Commands;

use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands;

class MaillogCommands extends DrushCommands {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a MaillogCommands object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Clear maillog entries
   *
   * @command maillog:clear
   */
  public function clear() {
    $this->database->truncate('maillog')->execute();
    $this->io()->info('All maillog entries have been deleted.');
  }

}
