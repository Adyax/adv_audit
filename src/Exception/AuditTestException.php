<?php

namespace Drupal\adv_audit\Exception;

use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Defines the migrate exception class.
 */
class AuditTestException extends \Exception {

  /**
   * The level of the error being reported.
   *
   * The value is a Migration::MESSAGE_* constant.
   *
   * @var int
   */
  protected $level;

  /**
   * The status to record in the map table for the current item.
   *
   * The value is a AuditResultResponseInterface::STATUS_* constant.
   *
   * @var int
   */
  protected $status;

  /**
   * Constructs a MigrateException object.
   *
   * @param string $message
   *   The message for the exception.
   * @param int $code
   *   The Exception code.
   * @param \Exception $previous
   *   The previous exception used for the exception chaining.
   * @param int $level
   *   The level of the error, a AdvAuditCheckInterface::MESSAGE_* constant.
   * @param int $status
   *   The status of the item for the map table, a AdvAuditCheckInterface::STATUS_*
   *   constant.
   */
  public function __construct($message = NULL, $code = 0, \Exception $previous = NULL, $level = AdvAuditCheckInterface::MESSAGE_ERROR, $status = AuditResultResponseInterface::RESULT_FAIL) {
    $this->level = $level;
    $this->status = $status;
    parent::__construct($message);
  }

  /**
   * Gets the level.
   *
   * @return int
   *   An integer status code. @see AdvAuditCheckInterface::MESSAGE_*
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * Gets the status of the current item.
   *
   * @return int
   *   An integer status code. @see AuditResultResponseInterface::STATUS_*
   */
  public function getStatus() {
    return $this->status;
  }

}
