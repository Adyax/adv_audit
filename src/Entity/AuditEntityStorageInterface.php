<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\adv_audit\Entity\AuditEntityInterface;

/**
 * Defines the storage handler class for Audit Result entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Audit Result entity entities.
 *
 * @ingroup adv_audit
 */
interface AuditEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Audit Result entity revision IDs for a specific Audit Result entity.
   *
   * @param \Drupal\adv_audit\Entity\AuditEntityInterface $entity
   *   The Audit Result entity entity.
   *
   * @return int[]
   *   Audit Result entity revision IDs (in ascending order).
   */
  public function revisionIds(AuditEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Audit Result entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Audit Result entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\adv_audit\Entity\AuditEntityInterface $entity
   *   The Audit Result entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(AuditEntityInterface $entity);

  /**
   * Unsets the language for all Audit Result entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
