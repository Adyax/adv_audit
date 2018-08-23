<?php

namespace Drupal\adv_audit;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Audit Issue entity.
 *
 * @see \Drupal\adv_audit\Entity\IssueEntity.
 */
class IssueEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\adv_audit\Entity\IssueEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished audit issue entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published audit issue entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit audit issue entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete audit issue entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add audit issue entities');
  }

}
