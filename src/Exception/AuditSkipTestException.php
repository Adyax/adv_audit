<?php

namespace Drupal\adv_audit\Exception;

/**
 * This exception is thrown when a test should be skipped.
 */
class AuditSkipTestException extends \Exception {

  /**
   * Constructs a SkipTestException object.
   *
   * @param string $message
   *   The message for the exception.
   */
  public function __construct($message = NULL) {
    parent::__construct($message);
  }

}
