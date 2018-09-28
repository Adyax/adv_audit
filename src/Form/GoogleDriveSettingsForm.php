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
      ->set('google_folder', $form_state->getValue('google_folder'))
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
    if(empty($config->get('google_id')) || empty($config->get('google_secret'))) {
      $form['description'] = [
        '#markup' => '<p>To be allowed to save report you should type your Google Drive API ID and password</p>
        <p>There is <a href="https://developers.google.com/drive/api/v3/quickstart/php" target="_blank">link</a> 
        to get them. Press button "ENABLE THE DRIVE API"</p>',
      ];
    }

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
    $form['google_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Folder on Google Drive to upload file'),
      '#description' => $this->t('If empty or folder does not exists
        report will be uploaded into root of your Google Drive (case-sensitive)'),
      '#maxlength' => 255,
      '#default_value' => $config->get('google_folder'),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

}
