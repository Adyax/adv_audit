<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\AdvAuditCheckpointInterface;

/**
 * Check if agregation for js and css is enabled.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = js_css_agregation,
 *   label = @Translation('Javascript & CSS aggregation'),
 *   category = performance,
 *   status = TRUE,
 *   severity = high
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class JsCssAgregation implements AdvAuditCheckpointInterface {

  /**
   * Return string with check status.
   *
   * @return string
   *   possible values: 'success', 'fail', 'process'.
   */
  public function getProcessStatus() {
    return 'test';
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
   *   Array results where every item is associated array with keys.
   */
  public function getRecentReport() {
    return [];
  }

  /**
   * Process checkpoint review.
   */
  public function process() {

  }

}
