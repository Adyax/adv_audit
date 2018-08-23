<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Audit Issue entities.
 *
 * @ingroup adv_audit
 */
interface IssueEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Audit Issue name.
   *
   * @return string
   *   Name of the Audit Issue.
   */
  public function getName();

  /**
   * Sets the Audit Issue name.
   *
   * @param string $name
   *   The Audit Issue name.
   *
   * @return \Drupal\adv_audit\Entity\IssueEntityInterface
   *   The called Audit Issue entity.
   */
  public function setName($name);

  /**
   * Gets the Audit Issue creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Audit Issue.
   */
  public function getCreatedTime();

  /**
   * Sets the Audit Issue creation timestamp.
   *
   * @param int $timestamp
   *   The Audit Issue creation timestamp.
   *
   * @return \Drupal\adv_audit\Entity\IssueEntityInterface
   *   The called Audit Issue entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Audit Issue published status indicator.
   *
   * Unpublished Audit Issue are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Audit Issue is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Audit Issue.
   *
   * @param bool $published
   *   TRUE to set this Audit Issue to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\adv_audit\Entity\IssueEntityInterface
   *   The called Audit Issue entity.
   */
  public function setPublished($published);

  /**
   * Gets the Audit Issue revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Audit Issue revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\adv_audit\Entity\IssueEntityInterface
   *   The called Audit Issue entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Audit Issue revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Audit Issue revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\adv_audit\Entity\IssueEntityInterface
   *   The called Audit Issue entity.
   */
  public function setRevisionUserId($uid);

}
