<?php

namespace Drupal\adv_audit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckListManager;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Drupal\Core\Routing\RedirectDestinationInterface;

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
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckListManager $advAuditCheckListManager
   *   Use DI to work with services.
   * @param \Drupal\Core\State $state
   *   Use DI to work with state.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   Use DI to work with redirect destination.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdvAuditCheckListManager $advAuditCheckListManager, State $state, RedirectDestinationInterface $redirect_destination) {
    $this->configCategories = $config_factory->get('adv_audit.config');
    $this->checkPlugins = $advAuditCheckListManager->getPluginsByStatus();
    $this->state = $state;
    $this->config = $config_factory;
    $this->redirectDestination = $redirect_destination;
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
      $container->get('plugin.manager.adv_audit_checklist'),
      $container->get('state'),
      $container->get('redirect.destination')
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
        $key . '_status' => [
          '#type' => 'checkbox',
          '#default_value' => $category['status'],
          '#attributes' => [
            'class' => ['category-status'],
          ],
        ],
        '#attributes' => [
          'class' => ['category-wrapper'],
        ],
      ];

      // TODO: Remove when all categories will be ready.
      if (!isset($this->checkPlugins[$key])) {
        continue;
      }

      $current_url = $this->redirectDestination->get();
      foreach ($this->checkPlugins[$key] as $plugin) {
        $form['categories'][$key][$plugin['id']] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['plugin-wrapper'],
          ],
        ];

        $form['categories'][$key][$plugin['id']][$plugin['id']] = [
          '#type' => 'checkbox',
          '#title' => $plugin['info']['label'],
          '#default_value' => $plugin['info']['status'],
        ];
        $form['categories'][$key][$plugin['id']][$plugin['id'] . '_edit'] = [
          '#type' => 'link',
          '#title' => $this->t('Edit'),
          '#url' => Url::fromRoute('adv_audit.edit_checkpoint', ['plugin_id' => $plugin['id']], ['query' => ['destination' => $current_url]]),
          '#attributes' => [
            'class' => ['edit', 'edit-checkpoint'],
          ],
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

    // Save categories status.
    $config = $this->config->getEditable('adv_audit.config');
    $config_categories = $config->get('adv_audit_settings');
    foreach ($config_categories['categories'] as $key => &$category) {
      $category['status'] = $values[$key . '_status'];
    }
    $config->set('adv_audit_settings', $config_categories);
    $config->save();

    // Save plugin status.
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
