<?php

namespace Drupal\adv_audit;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class AuditReason.
 *
 * Use in audit test plugin for save perform result.
 *
 * @package Drupal\adv_audit
 */
class AuditReason {

  /**
   * The status code.
   *
   * @var int
   */
  protected $status;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $testId;

  /**
   * The main reason data.
   *
   * May use to determine why test was failed.
   *
   * @var array|null|string|void
   */
  protected $reason;

  /**
   * An associative array of replacements.
   *
   * @var array
   */
  protected $arguments;

  /**
   * AuditReason constructor.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param int $status
   *   The status of the perform test.
   * @param null|mixed $reason
   *   Reason why test is failed. (optional)
   * @param array|mixed $arguments
   *   (optional) An associative array of replacements to make after
   *   translation of status message.
   */
  public function __construct($plugin_id, $status, $reason = [], $arguments = []) {
    $this->status = $status;
    $this->testId = $plugin_id;
    $this->reason = '';
    if (!is_array($reason)) {
      $reason = [$reason];
    }
    foreach ($reason as $key => $string) {
      if ($string instanceof TranslatableMarkup) {
        $reason[$key] = $string->__toString();
      }
    }
    $this->reason = $reason;
    $this->arguments = $arguments;
  }

  /**
   * Static method for create class instance.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param int $status
   *   The status of the perform test.
   * @param null|mixed $reason
   *   Reason why test is failed. (optional)
   * @param array|mixed $arguments
   *   (optional) An associative array of replacements to make after
   *   translation of status message.
   *
   * @return static
   */
  public static function create($plugin_id, $status, $reason = NULL, $arguments = []) {
    return new static($plugin_id, $status, $reason, $arguments);
  }

  /**
   * Return status of the test perform.
   *
   * @return mixed
   *   Return status code from AuditResultResponseInterface.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Get arguments value.
   *
   * @return array
   *   List of saved arguments.
   */
  public function getArguments() {
    return $this->arguments;
  }

  /**
   * Get list of available reasons from saved object.
   *
   * @return array
   *   Return array of reasons.
   */
  public function getReason() {
    return $this->reason;
  }

  /**
   * Check what current audit is pass all checks.
   *
   * @return bool
   *   Return TRUE if current audit is pass, otherwise FALSE.
   */
  public function isPass() {
    return $this->getStatus() == AuditResultResponseInterface::RESULT_PASS;
  }

  /**
   * Get current audit plugin id.
   *
   * @return string
   *   The plugin id value.
   */
  public function getPluginId() {
    return $this->testId;
  }

}
