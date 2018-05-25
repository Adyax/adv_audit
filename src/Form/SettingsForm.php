<?php

namespace Drupal\adv_audit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Settings page for Advanced Audit.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Use DI to work with congig.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configCategories = $config_factory->get('adv_audit.config');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adv-audit-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['administration'] = [
      '#type' => 'container',
      'actions' => [
        'rebuild_check_points' => [
          '#type' => 'submit',
          '#value' => $this->t('Rebuild check points'),
          '#name' => 'rebuild_check_points',
        ],
      ],
    ];
    $config = $this->configCategories->get('adv_audit_settings');
    $form['categories'] = [
      '#type' => 'container',
    ];
    foreach ($config['categories'] as $key => $category) {
      $form['categories'][$key] = [
        '#type' => 'fieldset',
        '#title' => $category['label'],
      ];
    }
    $form['#attached']['library'][] = 'adv_audit/adv_audit.admin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adv_audit_settings'];
  }

}
