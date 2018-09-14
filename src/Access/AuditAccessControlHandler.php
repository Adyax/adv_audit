<?php

namespace Drupal\adv_audit\Access;

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
        $access_result = AccessResult::allowedIfHasPermission($account, 'view adv_audit entity');
        break;

      case 'edit':
        $access_result = AccessResult::allowedIfHasPermission($account, 'edit adv_audit entity');
        break;

      case 'delete':
        $access_result = AccessResult::allowedIfHasPermission($account, 'delete adv_audit entity');
        break;

      default:
        $access_result = AccessResult::forbidden();
    }

    return $access_result;
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
