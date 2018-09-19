<?php

namespace Drupal\adv_audit\Sonar\Api;

use SonarQube\Api\AbstractApi;
use SonarQube\Api\Interfaces\AuthenticationInterface;

/**
 * Class Authentication.
 *
 * @package SonarQube\Api
 */
class Authentication extends AbstractApi implements AuthenticationInterface {

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return $this->get('authentication/validate');
  }

}
