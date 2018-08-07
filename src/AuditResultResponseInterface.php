<?php

namespace Drupal\adv_audit;

use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

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
   * The test is failed.
   * Or passed with the error.
   * Or Skipped.
   */
  const RESULT_WARN = 2;

  const RESULT_SKIP = 2;

  /**
   * Additional states of result.
   *
   * The test is passed.
   * Or passed with the error.
   */
  const RESULT_INFO = 3;

  /**
   * Add result of the running test.
   *
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckInterface $test
   *   The test plugin instance.
   * @param $status
   *   Execution status.
   *
   * @return void
   */
  public function addResultReport(AdvAuditCheckInterface $test, $status);

  public function calculateScore();


}