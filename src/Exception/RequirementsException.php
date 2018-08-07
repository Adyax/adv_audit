<?php

namespace Drupal\adv_audit\Exception;

use Exception;

/**
 * Defines an exception thrown when a test does not meet the requirements.
 *
 * @see \Drupal\adv_audit\Plugin\RequirementsInterface
 */
class RequirementsException extends \RuntimeException {

  /**
   * The missing requirements.
   *
   * @var array
   */
  protected $requirements;

  /**
   * Constructs a new RequirementsException instance.
   *
   * @param string $message
   *   (optional) The Exception message to throw.
   * @param array $requirements
   *   (optional) The missing requirements.
   * @param int $code
   *   (optional) The Exception code.
   * @param \Exception $previous
   *   (optional) The previous exception used for the exception chaining.
   */
  public function __construct($message = "", array $requirements = [], $code = 0, Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);

    $this->requirements = $requirements;
  }

  /**
   * Get an array of requirements.
   *
   * @return array
   *   The requirements.
   */
  public function getRequirements() {
    return $this->requirements;
  }

  /**
   * Get the requirements as a string.
   *
   * @return string
   *   A formatted requirements string.
   */
  public function getRequirementsString() {
    $output = '';
    foreach ($this->requirements as $requirement_type => $requirement) {
      if (!is_array($requirement)) {
        $requirement = [$requirement];
      }

      foreach ($requirement as $value) {
        $output .= "$requirement_type: $value. ";
      }
    }
    return trim($output);
  }

}
