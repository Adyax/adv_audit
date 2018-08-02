<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Advances audit check plugins.
 */
interface AdvAuditCheckInterface extends PluginInspectionInterface {


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
   * @return int
   *   Return one of value from AuditResultResponseInterface.
   */
  public function perform();

  /**
   * Additional configuration form for plugin instance.
   * Value will be store in state storage and can be uses bu next key:
   *   - adv_audit.plugin.PLUGIN_ID.config.KEY
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function configForm($form, FormStateInterface $form_state);

  public function checkRequirements();

  /**
   * Return plugin instance category.
   *
   * @return mixed
   */
  public function getCategoryDefinitionPlugin();

}
