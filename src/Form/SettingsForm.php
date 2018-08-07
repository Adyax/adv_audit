<?php

namespace Drupal\adv_audit\Form;

use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;

/**
 * Settings page for Advanced Audit.
 */
class SettingsForm extends ConfigFormBase {

  protected $auditPluginManager;

  protected $configCategories;

  protected $state;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Use DI to work with config.
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckManager $advAuditCheckListManager
   *   Use DI to work with services.
   * @param \Drupal\Core\State\State $state
   *   Use DI to work with state.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   Use DI to work with redirect destination.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdvAuditCheckManager $advAuditCheckListManager, State $state, RedirectDestinationInterface $redirect_destination) {
    $this->configCategories = $config_factory->get('adv_audit.config');
    $this->auditPluginManager = $advAuditCheckListManager;
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
      $container->get('plugin.manager.adv_audit_check'),
      $container->get('state'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Get untrusted roles.
   */
  protected function untrustedRoles() {
    return $this->config->getEditable('adv_audit.config')
      ->get('untrusted_roles');
  }

  /**
   * Returns the default untrusted roles.
   *
   * The default untrusted roles are:
   *   Anonymous      : always
   *   Authenticated  : if visitors are allowed to create accounts.
   *
   * @return string[]
   *   Default untrusted roles' IDs.
   */
  public function defaultUntrustedRoles() {
    // Add the Anonymous role to the output array.
    $roles = [AccountInterface::ANONYMOUS_ROLE];

    // Check whether visitors can create accounts.
    $user_register = $this->config->get('user.settings')
      ->get('register');
    if ($user_register !== USER_REGISTER_ADMINISTRATORS_ONLY) {
      // If visitors are allowed to create accounts they are considered
      // untrusted.
      $roles[] = AccountInterface::AUTHENTICATED_ROLE;
    }

    // Return the untrusted roles.
    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $categories = $this->getCategories();

    // Get the user roles.
    $roles = user_roles();
    $options = [];
    foreach ($roles as $rid => $role) {
      $options[$rid] = $role->label();
    }

    // Notify the user if anonymous users can create accounts.
    $message = '';
    if (in_array(AccountInterface::AUTHENTICATED_ROLE, $this->defaultUntrustedRoles())) {
      $message = $this->t('You have allowed anonymous users to create accounts without approval so the authenticated role defaults to untrusted.');
    }

    // Show the untrusted roles form element.
    $form['untrusted_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Untrusted roles'),
      '#description' => $this->t(
        'Define which roles are for less trusted users. The anonymous role defaults to untrusted. @message Most Security Review checks look for resources usable by untrusted roles.',
        ['@message' => $message]
      ),
      '#options' => $options,
      '#default_value' => $this->untrustedRoles(),
    ];

    $form['categories'] = [
      '#type' => 'container',
    ];
    foreach ($categories as $key => $category) {
      if (!isset($this->checkPlugins[$key])) {
        continue;
      }

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
          '#title' => $plugin['label'],
          '#default_value' => $plugin['status'],
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
    $config->set('untrusted_roles', $values['untrusted_roles']);
    $config->save();

    // Save plugin status.
    foreach ($this->checkPlugins as $category_items) {
      foreach ($category_items as $plugin) {
        if ($plugin['status'] != $values[$plugin['id']]) {
          $plugin['status'] = $values[$plugin['id']];
          $key = 'adv_audit.' . $plugin['id'];
          $this->state->set($key, $plugin);
        }
      }
    }
  }

}
