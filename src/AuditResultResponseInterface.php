<?php

namespace Drupal\adv_audit;

use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

/**
 * Interface for Response audit's results.
 */
interface AuditResultResponseInterface {

  /**
   * Test is passed correctly.
   */
  const RESULT_FAIL = 'FAILED';

  /**
   * Test is failed..
   */
  const RESULT_PASS = 'PASSED';

  /**
   * The test was skipped.
   */
  const RESULT_SKIP = 'SKIPPED';

  /**
   * Add result of the running test.
   *
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckInterface $test
   *   The test plugin instance.
   * @param int $status
   *   Execution status.
   */
  public function addResultReport(AdvAuditCheckInterface $test, $status);

  /**
   * Calculate total score of audit.
   */
  public function calculateScore();

}
