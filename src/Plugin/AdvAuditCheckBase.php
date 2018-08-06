<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Advances audit check plugins.
 */
abstract class AdvAuditCheckBase extends PluginBase implements AdvAuditCheckInterface, RequirementsInterface {

  // Add common methods and abstract methods for your plugin type here.

  public function getMessage($type) {

  }

  public function getCategoryName() {}

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

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
  public function configForm($form, FormStateInterface $form_state) {
    // Define base form config.

    $form['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('')
    ];
  }

  public function checkRequirements() {

  }


}
