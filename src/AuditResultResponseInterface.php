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
  const RESULT_FAIL = 0;

  /**
   * Test is failed..
   */
  const RESULT_PASS = 1;

  /**
   * Additional states of result.
   *
   * @deprecated Please use one of FAIL or PASS status.
   */
  const RESULT_WARN = 2;

  /**
   * The test was skipped.
   */
  const RESULT_SKIP = 2;

  /**
   * Additional states of result.
   *
   * @deprecated will be removed.
   */
  const RESULT_INFO = 3;

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
