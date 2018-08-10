<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Advances audit check plugins.
 */
interface AdvAuditCheckInterface extends PluginInspectionInterface, RequirementsInterface {


  /**
   * Test error.
   */
  const MESSAGE_ERROR = 1;

  /**
   * Test warning.
   */
  const MESSAGE_WARNING = 2;

  /**
   * Test notice.
   */
  const MESSAGE_NOTICE = 3;

  /**
   * Test info.
   */
  const MESSAGE_INFORMATIONAL = 4;

  const SEVERITY_CRITICAL = 'critical';
  const SEVERITY_HIGH = 'high';
  const SEVERITY_LOW = 'low';


  /**
   * An alias for getPluginId() for backwards compatibility reasons.
   *
   * @return string
   *   The plugin_id of the plugin instance.
   */
  public function id();

  /**
   * Get the plugin label.
   *
   * @return string
   *   The label for this migration.
   */
  public function label();

  /**
   * The actual procedure of carrying out the check.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function perform();

}
