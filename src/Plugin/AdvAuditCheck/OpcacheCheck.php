<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Component\Utility\OpCodeCache;

/**
 * Checks Opcache enabled.
 *
 * @AdvAuditCheck(
 *   id = "opcache_check",
 *   label = @Translation("Check Opcache"),
 *   category = "server_configuration",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class OpcacheCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $opcache_enabled = OpCodeCache::isEnabled();
    if ($opcache_enabled) {
      return $this->success();
    }

    return $this->fail(t('Opcache is disabled.'));
  }

}
