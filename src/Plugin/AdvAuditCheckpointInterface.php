<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * An interface define basic structure for Advance Audit checkpoints.
 *
 * @package Drupal\adv_audit
 */
interface AdvAuditCheckpointInterface extends PluginInspectionInterface {

  /**
   * Return information about plugin according annotation.
   *
   * @return mixed
   *   Associated array.
   */
  public static function getInformation();

  /**
   * Return string with check status.
   *
   * @return string
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function getProcessStatus();

  /**
   * Set check status.
   *
   * @param string $status
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function setProcessStatus($status);

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
