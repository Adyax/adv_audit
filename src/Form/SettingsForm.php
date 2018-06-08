<?php

namespace Drupal\adv_audit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckpointManager;
use Drupal\Core\State\State;

/**
 * Settings page for Advanced Audit.
 */
class SettingsForm extends ConfigFormBase {

  protected $checkPlugins = [];
  protected $configCategories;
  protected $state;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Use DI to work with congig.
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckpointManager $advAuditCheckpointManager
   *   Use DI to work with services.
   * @param \Drupal\Core\State $state
   *   Use DI to work with state.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdvAuditCheckpointManager $advAuditCheckpointManager, State $state) {
    $this->configCategories = $config_factory->get('adv_audit.config');
    $this->checkPlugins = $advAuditCheckpointManager->getAdvAuditPlugins();
    $this->state = $state;
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.adv_audit_checkpoins'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $categories = $this->getCategories();
    $form['categories'] = [
      '#type' => 'container',
    ];
    foreach ($categories as $key => $category) {
      $form['categories'][$key] = [
        '#type' => 'fieldset',
        '#title' => $category['label'],
      ];

      foreach ($this->checkPlugins[$key] as $plugin) {
        $form['categories'][$key][$plugin['id']] = [
          '#type' => 'checkbox',
          '#title' => $plugin['info']['label'],
          '#default_value' => $plugin['info']['status'],
        ];
      }
    }
    $form['#attached']['library'][] = 'adv_audit/adv_audit.admin';
    return $form;
  }

  /**
   * Return list categories from config.
   *
   * @return mixed
   *   Array categories.
   */
  protected function getCategories() {
    return $this->configCategories->get('adv_audit_settings')['categories'];
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($this->checkPlugins as $category_items) {
      foreach ($category_items as $plugin) {
        if ($plugin['info']['status'] != $values[$plugin['id']]) {
          $plugin['info']['status'] = $values[$plugin['id']];
          $key = 'adv_audit.' . $plugin['id'];
          $this->state->set($key, $plugin['info']);
        }
      }
    }
  }

}
