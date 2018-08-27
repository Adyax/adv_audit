<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\Core\Database\Connection;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Check code for security issues.
 *
 * @AdvAuditCheck(
 *  id = "security_code_review",
 *  label = @Translation("Security Code Review"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class SecurityCodeReview extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {
  protected $results;
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    $this->traverser = new NodeTraverser;
    $this->test();
  }

  public function test() {
    $iterator = new \RecursiveDirectoryIterator(DRUPAL_ROOT . '/modules/', \RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new \RecursiveIteratorIterator($iterator);
    $extentions = ['.php', '.module'];
    $patern = "/(\\" . implode('\b|\b', $extentions) . "\b)/i";
    foreach ($files as $file) {
      if (preg_match($patern, $file->getFilename())) {
        $this->checkFile($file);
      }
    }
    $i = 0;
  }

  protected function checkFile($file){
    $code = file_get_contents($file->getRealPath());
    $stmts = $this->parser->parse($code);

  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  protected function scanDirectory($path, $extentions = []) {
    ini_set('max_execution_time', 300);

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
   * Get database tables.
   */
  protected function getTables() {
    $settings = $this->getPerformSettings();
    // Don't check some tables (ex. node).
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
    else {
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

    // Take possibility don't check some tables.
    $form['directories'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded tables'),
      '#defauln_value' => $settings['directories'],
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
      'directories' => '',
    ];
  }

}
