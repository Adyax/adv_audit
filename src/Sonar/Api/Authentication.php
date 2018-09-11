<?php

namespace Drupal\adv_audit\Sonar\Api;

use Buzz\Client\Curl;
use Drupal\adv_audit\Sonar\SonarHttpClient;
use SonarQube\Api\AbstractApi;
use SonarQube\Api\Interfaces\AuthenticationInterface;

/**
 * Class Authentication
 *
 * @package SonarQube\Api
 */
class Authentication extends AbstractApi implements AuthenticationInterface {

  /**
   * @inheritDoc
   */
  public function validate() {
    $this->client->listeners = [];
    return $this->get('authentication/validate');
  }

}
