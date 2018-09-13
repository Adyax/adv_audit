<?php

namespace Drupal\adv_audit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Audit Result entity.
 *
 * @package Drupal\adv_audit
 */
class AuditAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the adv_audit.routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view adv_audit entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit adv_audit entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete adv_audit entity');

      default:
        return AccessResult::forbidden();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add adv_audit entity');
  }

}
