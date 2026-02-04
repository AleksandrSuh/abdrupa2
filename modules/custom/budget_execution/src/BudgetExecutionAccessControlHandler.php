<?php

namespace Drupal\budget_execution;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the budget execution entity.
 */
class BudgetExecutionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\budget_execution\Entity\BudgetExecution $entity */

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view budget execution');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit budget execution');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete budget execution');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create budget execution');
  }

}
