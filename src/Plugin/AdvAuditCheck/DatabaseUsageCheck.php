<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;

/**
 * @AdvAuditCheck(
 *  id = "database_usage",
 *  label = @Translation("Database usage"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class DatabaseUsageCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * Length of the day in seconds.
   */
  const ALLOWED_TABLE_SIZE = '512M';

  /**
   * The State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new PerformanceViewsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
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

  /**
   * {@inheritdoc}
   */
  public function perform() {
    // @todo: change this query(from site audit module).
    /*$sql_query = 'SELECT SUM(TABLES.data_length + TABLES.index_length) ';
    $sql_query .= 'FROM information_schema.TABLES ';
    $sql_query .= 'WHERE TABLES.table_schema = :dbname ';
    $sql_query .= 'GROUP BY TABLES.table_schema ';
    $result = db_query($sql_query, array(
      ':dbname' => $db_spec['database'],
    ))->fetchField();
    */
    if (count($this->withoutCache)) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, $this->withoutCache);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
  }

}
