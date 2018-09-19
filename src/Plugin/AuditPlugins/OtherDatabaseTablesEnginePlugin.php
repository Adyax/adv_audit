<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check does the database contain MyISAM tables.
 *
 * @AuditPlugin(
 *   id = "database_tables_engine",
 *   label = @Translation("Database tables engine check."),
 *   requirements = {},
 *   category = "other",
 * )
 */
class OtherDatabaseTablesEnginePlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

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

    if (is_null($result)) {
      return $this->skip($this->t('Curent DB driver is not MySQL.'));
    }

    if (!empty($result['count'])) {
      $issues = [];
      $params['info'] = $result;

      foreach ($params['info']['tables'] as $table) {
        $issues['database_tables_engine_' . $table] = [
          '@issue_title' => 'There is table with MyISAM engine: @table',
          '@table' => $table,
        ];
      }

      return $this->fail(NULL, [
        'issues' => $issues,
        '%count' => $params['info']['count'],
      ]);
    }

    return $this->success();
  }

  /**
   * Calculation of tables engines counts.
   */
  private function calculateTablesEngines() {
    $connection_options = $this->connection->getConnectionOptions();

    if (empty($connection_options['driver']) || $connection_options['driver'] != 'mysql') {
      return NULL;
    }

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

    if (is_null($info)) {
      return NULL;
    }

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
