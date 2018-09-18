<?php

namespace Drupal\adv_audit\Sonar\Api;

use Drupal\adv_audit\Sonar\SonarClient;
use SonarQube\Api\AbstractApi;

/**
 * Class Dashboard.
 */
class Dashboard extends AbstractApi {

  public $metrics = [
    'bugs',
    'vulnerabilities',
    'code_smells',
    'coverage',
    'duplicated_blocks',
  ];

  /**
   * Define sonar client.
   *
   * @var $sonar
   */
  public function __construct(SonarClient $sonar) {
    $this->client = $sonar;
  }

}
