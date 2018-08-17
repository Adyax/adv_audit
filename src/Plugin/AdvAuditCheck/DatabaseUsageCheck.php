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
 * Provide checkpoint for database usage.
 *
 * @AdvAuditCheck(
 *  id = "database_usage",
 *  label = @Translation("Database usage"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class DatabaseUsageCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

  /**
   * The State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

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
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, AuditMessagesStorageInterface $messages_storage, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->messagesStorage = $messages_storage;
    $this->database = $database;
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
      $container->get('adv_audit.messages'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $settings = $this->getPerformSettings();

    // Transform Mb into bytes.
    $max_length = $settings['max_table_size'] * 1024 * 1024;

    // Don't check some tables (ex. node).
    $excluded_tables = trim($settings['excluded_tables']);
    $excluded_tables = explode(',', $excluded_tables);

    try {
      $query = $this->database->select('information_schema.TABLES', 'ist');
      $query->fields('ist', ['TABLE_NAME']);
      $query->addExpression('ROUND(DATA_LENGTH / 1024 / 1024)', 'data_length');
      $query->condition('ist.DATA_LENGTH', $max_length, '>');
      $query->condition('ist.table_schema', $this->database->getConnectionOptions()['database']);
      if (count($excluded_tables)) {
        $query->condition('ist.TABLE_NAME', $excluded_tables, 'NOT IN');
      }
      $result = $query->execute();
      $tables = $result->fetchAllAssoc('TABLE_NAME');

      if (count($tables)) {

        // Prepare argument to render.
        foreach ($tables as &$table) {
          $table = (array) $table;
        }
        return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, NULL, ['rows' => $tables]);
      }
    }
    catch (Exception $e) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_SKIP);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
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

    $form['max_table_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Max size of table'),
      '#description' => $this->t('Enter max size (Mb).'),
      '#default_value' => $settings['max_table_size'],
    ];

    // Take possibility don't check some tables.
    $form['excluded_tables'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded tables'),
      '#defauln_value' => $settings['excluded_tables'],
      '#description' => $this->t('List of tables, separated with coma without spaces.'),
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
      'max_table_size' => 512,
      'excluded_tables' => '',
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
