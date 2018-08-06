<?php

namespace Drupal\adv_audit\Message;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Defines a audit test message class.
 */
class AuditMessage implements AuditMessageInterface {

  /**
   * The map between check status and watchdog severity.
   *
   * @var array
   */
  protected $map = [
    'status' => RfcLogLevel::INFO,
    'error' => RfcLogLevel::ERROR,
  ];

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    $type = isset($this->map[$type]) ? $this->map[$type] : RfcLogLevel::NOTICE;
    \Drupal::logger('adv_audit')->log($type, $message);
  }

}
