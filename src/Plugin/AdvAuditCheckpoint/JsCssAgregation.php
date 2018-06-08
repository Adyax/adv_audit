<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\Component\Plugin\PluginBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckpointInterface;

/**
 * Check if agregation for js and css is enabled.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "js_css_agregation",
 *   label = @Translation("Javascript & CSS aggregation"),
 *   description = @Translation("Allows you to improve the frontend performance
 *   of your site."), category = "performance", status = TRUE, severity =
 *   "high"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class JsCssAgregation extends PluginBase implements AdvAuditCheckpointInterface {

  /**
   * Return information about plugin according annotation.
   *
   * @return mixed
   *   Associated array.
   */
  public static function getInformation() {
  }

  /**
   * Return string with check status.
   *
   * @return string
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function getProcessStatus() {
  }

  /**
   * Set check status.
   *
   * @param string $status
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function setProcessStatus($status) {
  }

  /**
   * Return stored from last checking data.
   *
   * @return mixed
   *   array results where every item is associated array with keys:
   *   'point_name', 'severity', 'status', 'description'.
   */
  public function getRecentReport() {
  }

  /**
   * Process checkpoint review.
   */
  public function process() {
  }
}
