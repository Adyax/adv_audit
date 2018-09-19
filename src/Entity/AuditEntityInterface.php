<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Audit Result entity entities.
 *
 * @ingroup adv_audit
 */
interface AuditEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Audit Result entity name.
   *
   * @return string
   *   Name of the Audit Result entity.
   */
  public function getName();

  /**
   * Sets the Audit Result entity name.
   *
   * @param string $name
   *   The Audit Result entity name.
   *
   * @return \Drupal\adv_audit\Entity\AuditEntityInterface
   *   The called Audit Result entity entity.
   */
  public function setName($name);

  /**
   * Gets the Audit Result entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Audit Result entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Audit Result entity creation timestamp.
   *
   * @param int $timestamp
   *   The Audit Result entity creation timestamp.
   *
   * @return \Drupal\adv_audit\Entity\AuditEntityInterface
   *   The called Audit Result entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Audit Result entity published status indicator.
   *
   * Unpublished Audit Result entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Audit Result entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Audit Result entity.
   *
   * @param bool $published
   *   TRUE to set this Audit Result entity to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\adv_audit\Entity\AuditEntityInterface
   *   The called Audit Result entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the Audit Result entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Audit Result entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\adv_audit\Entity\AuditEntityInterface
   *   The called Audit Result entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Audit Result entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Audit Result entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\adv_audit\Entity\AuditEntityInterface
   *   The called Audit Result entity entity.
   */
  public function setRevisionUserId($uid);

}
