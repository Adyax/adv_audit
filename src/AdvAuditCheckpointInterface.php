<?php

namespace Drupal\adv_audit;

/**
 * An interface define basic structure for Advance Audit checkpoints.
 *
 * @package Drupal\adv_audit
 */
interface AdvAuditCheckpointInterface {

  /**
   * Return string with check status.
   *
   * @return string
   *   possible values: 'success', 'fail', 'process'.
   */
  public function getProcessStatus();

  /**
   * Set check status.
   *
   * @param \Drupal\adv_audit\string $status
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function setProcessStatus(string $status);

  /**
   * Return stored from last checking data.
   *
   * @return mixed
   *   array results where every item is associated array with keys:
   *   'point_name', 'severity', 'status', 'description'.
   */
  public function getRecentReport();

  /**
   * Process checkpoint review.
   */
  public function process();

}
