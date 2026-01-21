<?php

namespace Drupal\budget_import\Service;

use Drupal\Core\Database\Connection;

/**
 * Сервис для получения бюджетных данных.
 */
class BudgetDataService {

  /**
   * База данных.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Конструктор.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Подключение к базе данных.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Получает данные для бюджета.
   *
   * @return array
   *   Массив с данными.
   */
  public function getBudgetData() {

    $query = $this->database->select('budget_incomes', 'bt');
    $query->fields('bt', ['year', 'category', 'amount']);
    // ... условия, сортировка и т.д.
    $results = $query->execute()->fetchAll();

    $data = [];
    foreach ($results as $row) {
      $data['incomes'][$row->year][$row->category] = intval($row->amount);
    }

    $query = $this->database->select('budget_expenses', 'bt');
    $query->fields('bt', ['year', 'category', 'amount']);
    $results = $query->execute()->fetchAll();

    foreach ($results as $row) {
      $data['expenses'][$row->year][$row->category] = intval($row->amount);
    }

    return $data;
  }

}
