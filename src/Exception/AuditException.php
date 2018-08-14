<?php

namespace Drupal\adv_audit\Exception;

/**
 * Defines the audit exception class.
 */
class AuditException extends \Exception {

  /**
   * The plugin id where was throw the error.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Constructs a AuditException object.
   *
   * @param string $message
   *   The message for the exception.
   * @param null|string $plugin_id
   *   The plugin id.
   */
  public function __construct($message = NULL, $plugin_id = NULL) {
    parent::__construct($message);
    $this->pluginId = $plugin_id;
  }

  /**
   * Get plugin id.
   *
   * @return string
   *   The plugin id value.
   */
  public function getPluginId() {
    return $this->pluginId;
  }

}
