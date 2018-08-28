<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

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
    $trusted_host_patterns = Settings::get('trusted_host_patterns');

    if (empty($trusted_host_patterns)) {
      return $this->fail("Trusted hosts param is empty.");
    }

    return $this->success();
  }

}
