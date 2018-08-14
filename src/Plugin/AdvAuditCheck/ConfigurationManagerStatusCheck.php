<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\update\UpdateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @AdvAuditCheck(
 *   id = "configuration_manager_status",
 *   label = @Translation("Configuration Manager"),
 *   category = "core_and_modules",
 *   severity = "high",
 *   requirements = {},
 *   enabled = true,
 * )
 */
class ConfigurationManagerStatusCheck extends AdvAuditCheckBase implements AdvAuditCheckInterface, ContainerFactoryPluginInterface {

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
    $is_overriden = $storage_comparer->createChangelist()->hasChanges();

    if ($is_overriden) {
      return new AuditReason(
        $this->id(),
        AuditResultResponseInterface::RESULT_FAIL,
        $this->t('There are differences between configurations stored in database and files.')
      );
    }

    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);

  }

}
