<?php

namespace Drupal\adv_audit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\adv_audit\AdvAuditCheckpointManager;

/**
 * Settings page for Advanced Audit.
 */
class SettingsForm extends ConfigFormBase {

  protected $checkPlugins = [];

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Use DI to work with congig.
   * @param \Drupal\adv_audit\AdvAuditCheckpointManager $advAuditCheckpointManager
   *   Use DI to work with services.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdvAuditCheckpointManager $advAuditCheckpointManager) {
    parent::__construct($config_factory);
    $this->configCategories = $config_factory->get('adv_audit.config');
    foreach ($advAuditCheckpointManager->getDefinitions() as $plugin) {
      $this->checkPlugins[$plugin['category']][] = $plugin;
    }
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
      $container->get('plugin.manager.adv_audit_checkpoins')
    );
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
          '#title' => $category['label'],
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

}
