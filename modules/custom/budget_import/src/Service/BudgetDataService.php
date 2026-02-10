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

    $query = $this->database->select('budget_mundolg', 'bt');
    $query->fields('bt', ['year', 'amount']);
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $data['mundolg'][$row->year] = intval($row->amount);
    }

    $query = $this->database->select('budget_execution_base', 'bt');
    $query->fields('bt', ['date', 'plan_value', 'actual_value', 'type', 'category_name']);
    $results = $query->execute()->fetchAll(); // income expense_sector
    foreach ($results as $row) {
      $data['execution'][$row->type][$row->date][$row->category_name] = ['plan' => intval($row->plan_value), 'actual' => intval($row->actual_value)];
    }

    return $data;
  }

}
