<?php

namespace Drupal\adv_audit\Plugin;

/**
 * An interface to check for a test plugin requirements.
 */
interface RequirementsInterface {

  /**
   * Checks if requirements for this plugin are OK.
   *
   * @throws \Drupal\adv_audit\Exception\RequirementsException
   *   Thrown when requirements are not met.
   */
  public function checkRequirements();

}