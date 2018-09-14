<?php
/**
 * Provide PluginFormInterface implementation.
 */

namespace Drupal\adv_audit\Traits;


use Drupal\Core\Form\FormStateInterface;

trait AuditPluginSubform {

  /**
   * @inheritdoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * @inheritdoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->pluginSettingsStorage->set(NULL, $form_state->getValues());
  }

}
