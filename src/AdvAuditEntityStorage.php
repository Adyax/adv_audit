<?php

namespace Drupal\adv_audit;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\adv_audit\Entity\AdvAuditEntityInterface;

/**
 * Defines the storage handler class for Audit Result entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Audit Result entity entities.
 *
 * @ingroup adv_audit
 */
class AdvAuditEntityStorage extends SqlContentEntityStorage implements AdvAuditEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(AdvAuditEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {adv_audit_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {adv_audit_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(AdvAuditEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {adv_audit_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('adv_audit_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
