<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\Core\Database\Connection;

/**
 * Check permission of untrusted roles.
 *
 * @AdvAuditCheck(
 *  id = "untrusted_roles_permission",
 *  label = @Translation("Untrusted role's permission"),
 *  category = "security",
 *  severity = "high",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class UntrastedRolesPermissions extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

  /**
   * The State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The audit messages storage service.
   *
   * @var \Drupal\adv_audit\Message\AuditMessagesStorageInterface
   */
  protected $messagesStorage;

  /**
   * Constructs a new PerformanceViewsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   Access to state service.
   * @param \Drupal\adv_audit\Message\AuditMessagesStorageInterface $messages_storage
   *   Interface for the audit messages.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, AuditMessagesStorageInterface $messages_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->messagesStorage = $messages_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('adv_audit.messages')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $settings = $this->getPerformSettings();

    // Transform Mb into bytes.
    $max_length = $settings['max_table_size'] * 1024 * 1024;

    try {
      $tables = $this->getTables();
      $status = AuditResultResponseInterface::RESULT_PASS;
      if (count($tables)) {
        foreach ($tables as $key => &$table) {
          // We can't compare calculated value in sql query.
          // So, we have to check this condition here.
          if ($table->data_length > $max_length) {
            $status = AuditResultResponseInterface::RESULT_FAIL;
            // Prepare argument to render.
            $table = [
              'name' => $table->relname,
              'size' => round($table->data_length / 1024 / 1024, 2),
            ];
          }
          else {
            unset($tables[$key]);
          }
        }
      }
      return new AuditReason($this->id(), $status, NULL, ['rows' => $tables]);
    } catch (Exception $e) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_SKIP);
    }
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.additional-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {

    $form = [];
    $settings = $this->getPerformSettings();

    // Get the user roles.
    $roles = user_roles();
    $options = [];
    foreach ($roles as $rid => $role) {
      $options[$rid] = $role->label();
    }

    $form['untrusted_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Untrusted roles.'),
      '#default_value' => $settings['untrusted_roles'],
      '#options' => $options
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    $value = $form_state->getValue('additional_settings');
    $this->state->set($this->buildStateConfigKey(), $value['plugin_config']);
  }

  /**
   * Get settings for perform task.
   */
  protected function getPerformSettings() {
    $settings = $this->state->get($this->buildStateConfigKey());
    return !is_null($settings) ? $settings : $this->getDefaultPerformSettings();
  }

  /**
   * Get default settings.
   */
  protected function getDefaultPerformSettings() {
    return [
      'untrusted_roles' => ['anonymous', 'authenticated'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    $build = [];

    if ($type === AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      $arguments = $reason->getArguments();
      $build = [
        '#type' => 'container',
      ];

      // Render tables.
      if (isset($arguments['rows'])) {
        $build['list'] = [
          '#type' => 'table',
          '#weight' => 1,
          '#header' => [
            $this->t('Name'),
            $this->t('Size (Mb)'),
          ],
          '#rows' => $arguments['rows'],
        ];
        unset($arguments['rows']);
      }

      // Get default fail message.
      $build['message'] = [
        '#weight' => 0,
        '#markup' => $this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_FAIL),
      ];
    }
    return $build;
  }

}
