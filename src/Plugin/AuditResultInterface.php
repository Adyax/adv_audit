<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\adv_audit\AuditReason;

/**
 * Defines an interface for Advances audit check plugins.
 */
interface AuditResultInterface {

  /**
   * Return Success result.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function success(): AuditReason;

  /**
   * Return Fail result.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function fail($msg, array $issue_details): AuditReason;

  /**
   * Return Skip result.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function skip($msg): AuditReason;

}
