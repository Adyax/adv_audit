<?php

namespace Drupal\adv_audit;

/**
 * Class AuditReason.
 * Use in audit test plugin for save perform result.
 *
 * @package Drupal\adv_audit
 */
class AuditReason {

  protected $status;
  protected $test_id;
  protected $message_type;


  public function __construct($plugin_id, $status, $msg_type) {
    $this->status = $status;
    $this->test_id = $plugin_id;
    $this->message_type = $msg_type;
  }

  public static function create($plugin_id, $status, $msg_type) {
    return new static($plugin_id, $status, $msg_type);
  }

  public function getStatus() {
    return $this->status;
  }


}