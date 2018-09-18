<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the storage handler class for Audit Issue entities.
 *
 * This extends the base storage class, adding required special handling for
 * Audit Issue entities.
 *
 * @ingroup adv_audit
 */
class IssueEntityStorage extends SqlContentEntityStorage implements IssueEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(IssueEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {adv_audit_issue_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {adv_audit_issue_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(IssueEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {adv_audit_issue_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('adv_audit_issue_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
