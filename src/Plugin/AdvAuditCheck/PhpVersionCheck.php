<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

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
    $phpversion = phpversion();

    if (version_compare($phpversion, DRUPAL_RECOMMENDED_PHP) < 0) {
      return $this->fail(NULL, ['%recommended' => DRUPAL_RECOMMENDED_PHP]);
    }

    return $this->success();
  }

}
