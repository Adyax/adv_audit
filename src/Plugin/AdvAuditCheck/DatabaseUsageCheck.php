<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\Core\Database\Connection;

/**
 * Check Database usage.
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
class DatabaseUsageCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

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
   * @param \Drupal\adv_audit\Message\AuditMessagesStorageInterface $messages_storage
   *   Interface for the audit messages.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AuditMessagesStorageInterface $messages_storage, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('adv_audit.messages'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $settings = $this->getSettings();

    // Transform Mb into bytes.
    $max_length = $settings['max_table_size'] * 1024 * 1024;
    $arguments = [];
    try {
      $tables = $this->getTables();
      if (count($tables)) {
        foreach ($tables as $table) {
          // We can't compare calculated value in sql query.
          // So, we have to check this condition here.
          if ($table->data_length > $max_length) {
            // Prepare argument to render.
            $arguments['issues'][$table->relname] = [
              '@issue_title' => '@table_name (@table_size Mb)',
              '@table_name' => $table->relname,
              '@table_size' => round($table->data_length / 1024 / 1024, 2),
            ];
          }
        }
      }
      if (!isset($arguments['issues']) || empty($arguments['issues'])) {
        return $this->success();
      }

      return $this->fail(NULL, $arguments);
    }
    catch (Exception $e) {
      return $this->skip($e->getMessage());
    }
  }

  /**
   * Get database tables.
   */
  protected function getTables() {
    $settings = $this->getSettings();
    $tables = [];
    // Exclude some tables (ex. node).
    $excluded_tables = trim($settings['excluded_tables']);
    $excluded_tables = explode(',', $excluded_tables);
    $db_type = $this->database->databaseType();
    $tb_name_key = 'relname';

    if ($db_type == 'pgsql') {
      $query = $this->database->select('pg_catalog.pg_statio_user_tables', 'ist');
      $query->fields('ist', [$tb_name_key]);
      $query->condition('ist.schemaname', 'public');
      if (count($excluded_tables)) {
        $query->condition('ist.relname', $excluded_tables, 'NOT IN');
      }
      $query->addExpression('pg_total_relation_size(relid)', 'data_length');
      $result = $query->execute();
      $tables = $result->fetchAllAssoc($tb_name_key);
    }
    elseif ($db_type == 'mysql') {
      $query = $this->database->select('information_schema.TABLES', 'ist');
      $query->fields('ist', ['TABLE_NAME']);
      $query->addExpression('DATA_LENGTH', 'data_length');
      $query->addExpression('TABLE_NAME', $tb_name_key);
      $query->condition('ist.table_schema', $this->database->getConnectionOptions()['database']);
      if (count($excluded_tables)) {
        $query->condition('ist.TABLE_NAME', $excluded_tables, 'NOT IN');
      }
      $result = $query->execute();
      $tables = $result->fetchAllAssoc('TABLE_NAME');
    }
    return $tables;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSettings();
    $form['max_table_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Max size of table'),
      '#description' => $this->t('Enter max size (Mb).'),
      '#default_value' => $settings['max_table_size'],
    ];
    return $form;

  }

}
