<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;

/**
 * Check does the database contain MyISAM tables.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "database_tables_engine",
 *   label = @Translation("Database tables engine check"),
 *   description = @Translation("Check does the database contain MyISAM tables"),
 *   category = "core_and_modules",
 *   status = TRUE,
 *   severity = "low"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class DatabaseTablesEngine extends AdvAuditCheckpointBase {

  protected $impactMessage = "InnoDB storage engine is a better than MyISAM.";

  protected $failMessage = "Database contains @count_tables.";

  protected $successMessage = "Database doesn't contain MyISAM tables.";

  protected $resultDescription = "All of the tables in your application should be using the InnoDB table engine. The main advantage to InnoDB is row level locking. While MyISAM can sometime be faster for reads in older version of MySQL, InnoDB will always out perform MyISAM if there is even a low level of writes to the tables. The other major problem with MyISAM is that it places a lock on the entire table when performing a mysqldump which is used for backups. This effectively renders the site unusable while the database backup is being made. In the most recent version of MySQL, InnoDB outperforms MyISAM in all metrics.";

  protected $tablesEnginesCounts = [];

  /**
   * Return information about process result.
   *
   * @return mixed
   *   Provide result of process.
   */
  public function getProcessResult($params = []) {
    if ($this->getProcessStatus() == 'fail') {
      $fail_message = $this->get('fail_message');
      if (!empty($fail_message)) {
        $count_tables = \Drupal::translation()->formatPlural($this->tablesEngineCount('MyISAM'), '1 MyISAM table', '@count MyISAM tables')->__toString();
        // @codingStandardsIgnoreLine
        return $this->t($fail_message, ['@count_tables' => $count_tables]);
      }
      else {
        return '';
      }
    }

    $success_message = $this->get('success_message');
    // @codingStandardsIgnoreLine
    return !empty($success_message) ? $this->t($success_message) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return $this->t('Check does the database contain MyISAM tables');
  }

  /**
   * Process checkpoint review.
   */
  public function process() {

    // Set process status 'fail' if db contains MyISAM tables.
    if ($this->tablesEngineCount('MyISAM') > 0) {
      $this->setProcessStatus('fail');
    }
    else {
      $this->setProcessStatus('success');
    }

    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->get('result_description'),
      'information' => $this->getProcessResult(),
      'status' => $this->getProcessStatus(),
      'severity' => $this->get('severity'),
      'actions' => $this->getActions(),
      'impacts' => $this->getImpacts(),
    ];

    $results[$this->get('category')][$this->getPluginId()] = $result;

    return $results;
  }

  /**
   * Calculation of tables engines counts.
   */
  protected function calculateTablesEnginesCount() {
    $connection_options = \Drupal::database()->getConnectionOptions();

    $sql = 'SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.TABLES WHERE TABLES.table_schema = :db';
    $query = db_query($sql, [':db' => $connection_options['database']]);

    $this->tablesEnginesCounts = array_count_values($query->fetchAllKeyed());
  }

  /**
   * Count of tables with searched engine.
   *
   * @param string $engine
   *   Table engine.
   *
   * @return int
   *   Count of tables.
   */
  protected function tablesEngineCount($engine) {
    if (empty($this->tablesEnginesCounts)) {
      $this->calculateTablesEnginesCount();
    }

    return (!empty($this->tablesEnginesCounts[$engine])) ? $this->tablesEnginesCounts[$engine] : 0;
  }

}
