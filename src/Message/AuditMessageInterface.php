<?php

namespace Drupal\adv_audit\Message;

interface AuditMessageInterface {

  /**
   * Displays a audit message.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message, for example: status or warning.
   */
  public function display($message, $type = 'status');

}