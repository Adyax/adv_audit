<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;

/**
 * Register Globals Check plugin class.
 *
 * @AdvAuditCheck(
 *   id = "register_globals_check",
 *   label = @Translation("PHP register globals"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class RegisterGlobalsCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $status = AuditResultResponseInterface::RESULT_PASS;
    $register_globals = trim(ini_get('register_globals'));
    if (!empty($register_globals) && strtolower($register_globals) != 'off') {
      $status = AuditResultResponseInterface::RESULT_FAIL;
    }

    return new AuditReason($this->id(), $status, NULL, []);
  }

}
