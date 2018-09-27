<?php

namespace Drupal\adv_audit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GoogleDriveSettingsForm.
 */
class GoogleDriveSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adv_audit.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'googledrive_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->configFactory->getEditable('adv_audit.settings')
      // Set the submitted configuration setting
      ->set('google_id', $form_state->getValue('google_id'))
      // You can set multiple configurations at once by making
      // multiple calls to set()
      ->set('google_secret', $form_state->getValue('google_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form for Google Drive Settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adv_audit.settings');
    $form['google_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#maxlength' => 255,
      '#default_value' => $config->get('google_id'),
      '#required' => TRUE,
    ];
    $form['google_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#maxlength' => 255,
      '#default_value' => $config->get('google_secret'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

}
