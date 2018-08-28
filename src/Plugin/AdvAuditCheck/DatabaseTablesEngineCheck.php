<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check does the database contain MyISAM tables.
 *
 * @AdvAuditCheck(
 *   id = "database_tables_engine",
 *   label = @Translation("Database tables engine check."),
 *   requirements = {},
 *   category = "other",
 *   enabled = true,
 *   severity = "low"
 * )
 */
class DatabaseTablesEngineCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $params = [];
    $result = $this->tablesEngines('MyISAM');

    if (!empty($result['count'])) {
      $params['info'] = $result;
      return $this->fail($this->t('There are tables with MyISAM engine.'), $params);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type != AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      return [];
    }

    $arguments = $reason->getArguments();
    if (empty($arguments)) {
      return [];
    }

    $markup_key = '#markup';
    $message = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['actions-message'],
      ],
    ];
    $message['msg'][$markup_key] = $this->t('There are number of MyISAM tables: @count',
      ['@count' => $arguments['info']['count']]);

    $list = [
      '#theme' => 'item_list',
    ];
    $items = [];
    foreach ($arguments['info']['tables'] as $table) {
      $item[$markup_key] = $table;
      $items[] = $item;
    }
    $list['#items'] = $items;

    return [$message, $list];
  }

  /**
   * Calculation of tables engines counts.
   */
  private function calculateTablesEngines() {
    $connection_options = $this->connection->getConnectionOptions();

    $sql = 'SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.TABLES WHERE TABLES.table_schema = :db';
    $query = $this->connection->query($sql, [':db' => $connection_options['database']]);

    return $query->fetchAllKeyed();
  }

  /**
   * Count of tables with searched engine.
   *
   * @param string $engine
   *   Table engine.
   *
   * @return array
   *   Count of tables and tables.
   */
  private function tablesEngines($engine) {
    $info = $this->calculateTablesEngines();
    $counts = array_count_values($info);

    if (!empty($counts[$engine])) {
      return [
        'count' => $counts[$engine],
        'tables' => array_keys($info, $engine),
      ];
    }
    else {
      return ['count' => 0];
    }
  }

}
