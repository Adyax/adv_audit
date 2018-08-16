<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;

use Drupal\Core\Site\Settings;

/**
 * Trusted Host Check plugin class.
 *
 * @AdvAuditCheck(
 *   id = "trusted_host_check",
 *   label = @Translation("Trusted Host Settings"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class TrustedHostCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $params = [];
    $reason = NULL;
    $status = AuditResultResponseInterface::RESULT_PASS;
    $trusted_host_patterns = Settings::get('trusted_host_patterns');

    if (empty($trusted_host_patterns)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
    }
    else {
      $params = ['%trusted_host_patterns' => implode(', ', $trusted_host_patterns)];
    }

    return new AuditReason($this->id(), $status, $reason, $params);
  }

}
