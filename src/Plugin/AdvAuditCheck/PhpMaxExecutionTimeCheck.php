<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
/**
 * @AdvAuditCheck(
 *   id = "php_max_execution_time",
 *   label = @Translation("Checking php max_execution_time setting"),
 *   category = "performance",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class PhpMaxExecutionTimeCheck extends AdvAuditCheckBase {

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $time = intval(ini_get('max_execution_time'));

    $status = AuditResultResponseInterface::RESULT_PASS;
    if ($time > 300 || $time === 0) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
    }

    return new AuditReason($this->id(), $status, NULL, []);
  }

}
