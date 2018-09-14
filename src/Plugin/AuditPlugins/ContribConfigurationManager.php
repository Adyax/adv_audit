<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Link;
use Drupal\update\UpdateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if configuration is overridden.
 *
 * @AuditPlugin(
 *   id = "configuration_manager_status",
 *   label = @Translation("Configuration Manager"),
 *   category = "core_and_modules",
 *   severity = "high",
 *   requirements = {},
 *   enabled = true,
 * )
 */
class ContribConfigurationManager extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  protected $updateManager;

  protected $configManager;

  protected $activeStorage;

  protected $snapshotStorage;

  protected $syncStorage;

  /**
   * Constructs Configuration Manager Status.
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    UpdateManager $update_manager,
    ConfigManager $config_manager,
    CachedStorage $config_storage,
    DatabaseStorage $config_storage_snapshot,
    StorageInterface $config_storage_sync
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->updateManager = $update_manager;
    $this->configManager = $config_manager;
    $this->activeStorage = $config_storage;
    $this->snapshotStorage = $config_storage_snapshot;
    $this->syncStorage = $config_storage_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('update.manager'),
      $container->get('config.manager'),
      $container->get('config.storage'),
      $container->get('config.storage.snapshot'),
      $container->get('config.storage.sync')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {

    $storage_comparer = new StorageComparer($this->syncStorage, $this->activeStorage, $this->configManager);
    $source_list = $this->syncStorage->listAll();
    $overridden = $storage_comparer->createChangelist()->hasChanges();
    if (empty($source_list) || !$overridden) {
      return $this->success();
    }

    // Configuration is overridden.
    return $this->fail(NULL, [
      'issues' => [
        'configuration_manager_status' => [
          '@issue_title' => 'Configuration is overridden',
        ],
      ],
      '%link' => Link::createFromRoute($this->t('configuration synchronization'), 'config.sync')
        ->toString(),
    ]);
  }

}
