<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Entity\Index;

/**
 * Checks Search API solr indexes and servers status.
 *
 * @AdvAuditCheck(
 *   id = "solr_usage",
 *   label = @Translation("Solr usage"),
 *   category = "performance",
 *   requirements = {
 *     "module": {
 *      "search_api:1.9",
 *      "search_api_solr:1.2"
 *     },
 *   },
 *   enabled = true,
 *   severity = "high"
 * )
 */
class SolrUsageCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, AdvAuditCheckInterface, ContainerFactoryPluginInterface {

  /**
   * The search server storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $serverStorage;

  /**
   * Not fully indexed indexes.
   *
   * @var array
   */
  protected $notFullyIndexed = [];

  /**
   * The search servers that are unavailable.
   *
   * @var array
   */
  protected $unavailableServers = [];

  /**
   * The search servers with no indexes.
   *
   * @var array
   */
  protected $noIndexesServers = [];

  /**
   * The search servers with no active indexes.
   *
   * @var array
   */
  protected $noActiveIndexesServers = [];

  /**
   * Constructs a new CronSettingsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $server_storage
   *   The search_api_server storage implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigEntityStorageInterface $server_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->serverStorage = $server_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('search_api_server')
    );
  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $status = AuditResultResponseInterface::RESULT_PASS;
    $params = [];

    $query = \Drupal::entityQuery('search_api_server');
    $server_ids = $query->execute();

    if (!empty($server_ids)) {
      foreach ($server_ids as $server_id) {
        $this->collectServerInfo($server_id);
      }
    }

    if (!empty($this->notFullyIndexed)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $params['not_fully_indexed'] = $this->notFullyIndexed;
    }

    if (!empty($this->unavailableServers)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $params['unavailable_servers'] = $this->unavailableServers;
    }

    if (!empty($this->unavailableServers)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $params['unavailable_servers'] = $this->unavailableServers;
    }

    if (!empty($this->noIndexesServers)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $params['no_indexes_servers'] = $this->noIndexesServers;
    }

    if (!empty($this->noActiveIndexesServers)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $params['no_active_indexes_servers'] = $this->noActiveIndexesServers;
    }

    return new AuditReason($this->id(), $status, NULL, $params);
  }

  /**
   * Collects info about servers.
   *
   * @param string $server_id
   *   Server id.
   */
  private function collectServerInfo($server_id) {
    $server = $this->serverStorage->load($server_id);
    if (!$server->getBackend()->isAvailable()) {
      $this->unavailableServers[] = $server_id;
      return;
    }
    $indexes = $server->getIndexes();

    if (count($indexes)) {
      $active = 0;
      foreach ($indexes as $index) {
        $this->collectIndexInfo($index, $server_id, $active);
      }

      if ($active == 0) {
        $this->noActiveIndexesServers[] = $server_id;
      }
    }
    else {
      $this->noIndexesServers[] = $server;
    }
  }

  /**
   * Collects info about indexes.
   *
   * @param \Drupal\search_api\Entity\Index $index
   *   Index object.
   * @param string $server_id
   *   Server id.
   * @param int $active
   *   Active indexes counter.
   */
  private function collectIndexInfo(Index $index, $server_id, &$active) {
    if ($index->status()) {
      $active++;

      $tracker = $index->hasValidTracker() ? $index->getTrackerInstance() : NULL;

      $indexed_count = $tracker->getIndexedItemsCount();
      $total_count = $tracker->getTotalItemsCount();

      if ($indexed_count < $total_count) {
        $this->notFullyIndexed[] = $server_id . '.' . $index->id();
      }
      else {
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type != AuditMessagesStorageInterface::MSG_TYPE_ACTIONS) {
      return [];
    }

    $arguments = $reason->getArguments();
    if (empty($arguments)) {
      return [];
    }

    $markup_key = '#markup';
    $message = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('There are number of issues.'),
      '#attributes' => [
        'class' => ['actions-message'],
      ],
    ];

    $list = [
      '#theme' => 'item_list',
    ];
    $items = [];
    foreach ($arguments as $issue => $entities) {
      $item[$markup_key] = $issue;
      $item['children'] = ['#theme' => 'item_list'];
      foreach ($entities as $entity) {
        $item['children']['#items'][] = [$markup_key => $entity];
      }

      $items[] = $item;
    }
    $list['#items'] = $items;

    return [$message, $list];
  }

}
