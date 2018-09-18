<?php

namespace Drupal\adv_audit\Traits;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provide PluginFormInterface implementation.
 */
trait AuditPluginSubform {

  /**
   * Validation function for config form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Submit function for config form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->pluginSettingsStorage->set(NULL, $form_state->getValues());
  }

}
