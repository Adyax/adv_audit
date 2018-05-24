<?php

namespace Drupal\adv_audit;

/**
 * Interface AdvAuditCheckpointInterface
 *   An interface define basic structure for Advance Audit checkpoints.
 *
 * @package Drupal\adv_audit
 */
interface AdvAuditCheckpointInterface {

  /**
   * @return mixed
   *   Return string with check status, possible values: 'success', 'fail',
   *   'process',
   */
  public function getProcessStatus();

  /**
   * @param \Drupal\adv_audit\string $status
   *   Set check status, possible values: 'success', 'fail', 'process',
   */
  public function setProcessStatus(string $status);

  /**
   * @return mixed
   *   Return stored from last checking data.
   */
  public function getRecentReport();

  /**
   * Process checkpoint review.
   */
  public function process();
}