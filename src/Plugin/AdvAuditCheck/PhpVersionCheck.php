<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;

/**
 * PHP Version Check plugin class.
 *
 * @AdvAuditCheck(
 *   id = "php_version_check",
 *   label = @Translation("PHP Version"),
 *   category = "server_configuration",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class PhpVersionCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $params = [];
    $reason = NULL;
    $status = AuditResultResponseInterface::RESULT_PASS;

    $phpversion = phpversion();

    if (version_compare($phpversion, DRUPAL_RECOMMENDED_PHP) < 0) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $params = ['%recommended' => DRUPAL_RECOMMENDED_PHP];
    }

    return new AuditReason($this->id(), $status, $reason, $params);
  }

}
