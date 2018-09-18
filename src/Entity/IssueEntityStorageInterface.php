<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface IssueEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Audit Issue revision IDs for a specific Audit Issue.
   *
   * @param \Drupal\adv_audit\Entity\IssueEntityInterface $entity
   *   The Audit Issue entity.
   *
   * @return int[]
   *   Audit Issue revision IDs (in ascending order).
   */
  public function revisionIds(IssueEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Audit Issue author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Audit Issue revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\adv_audit\Entity\IssueEntityInterface $entity
   *   The Audit Issue entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(IssueEntityInterface $entity);

  /**
   * Unsets the language for all Audit Issue with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
