<?php

namespace Drupal\adv_audit;

/**
 * Class AuditReason.
 *
 * Use in audit test plugin for save perform result.
 *
 * @package Drupal\adv_audit
 */
class AuditReason {

  protected $status;
  protected $testId;
  protected $messageType;

  /**
   * Constructs AuditReason.
   */
  public function __construct($plugin_id, $status, $msg_type) {
    $this->status = $status;
    $this->testId = $plugin_id;
    $this->messageType = $msg_type;
  }

  /**
   * {@inheritdoc}
   */
  public static function create($plugin_id, $status, $msg_type) {
    return new static($plugin_id, $status, $msg_type);
  }

  /**
   * Get Status of audit.
   */
  public function getStatus() {
    return $this->status;
  }

}
