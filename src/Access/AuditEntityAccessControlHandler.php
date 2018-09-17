<?php

namespace Drupal\adv_audit\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Audit Result entity entity.
 *
 * @see \Drupal\adv_audit\Entity\AuditEntity.
 */
class AuditEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\adv_audit\Entity\AuditEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished audit result entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published audit result entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit audit result entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete audit result entity entities');

      default:
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add audit result entity entities');
  }

}
