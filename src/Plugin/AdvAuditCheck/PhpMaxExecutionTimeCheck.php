<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

/**
 * Check PHP `max_execution_time`.
 *
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

    if ($time > 300 || $time === 0) {
      $this->fail($this->t("Max execution time is too high."));
    }

    return $this->success();
  }

}
