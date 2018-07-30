<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;
use Drupal\Core\Config\StorageComparer;

/**
 * Check non-security updates for contrib modules.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "configuration_manager_status",
 *   label = @Translation("Configuration Manager"),
 *   description = @Translation("Check current status of config files via
 *   configuration manager and provide the status."), category =
 *   "core_and_modules", status = TRUE, severity = "high"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class ConfigirationManagerStatus extends AdvAuditCheckpointBase {

  public $actionMessage = 'Review and update configurations';

  public $impactMessage = 'There are should not be any changes in config files because next deployment may revert all that changes. All the configuration changes on the site should be immediately imported into the configuration files through the config manager and must be updated in the project code.';

  public $failMessage = 'There are differences between configurations stored in database and files.';

  public $successMessage = 'All config files are actual.';

  /**
   * Store implementation of update.manager service.
   *
   * @var \Drupal\update\UpdateManagerInterface
   *   Update manager object.
   */
  protected $updateManager;

  /**
   * Number security updates.
   *
   * @var mixed
   *   Modules count.
   */
  protected $count;

  protected $additionalServices = [
    'updateManager' => 'update.manager',
    'stringTranslation' => 'string_translation',
    'configManager' => 'config.manager',
    'activeStorage' => 'config.storage',
    'snapshotStorage' => 'config.storage.snapshot',
    'syncStorage' => 'config.storage.sync',
  ];

  /**
   * Process checkpoint review.
   */
  public function process() {
    $storage_comparer = new StorageComparer($this->syncStorage, $this->activeStorage, $this->configManager);
    $is_overriden = $storage_comparer->createChangelist()->hasChanges();
    if ($is_overriden) {
      $this->setProcessStatus('fail');
    }
    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->getDescription(),
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
   * {@inheritdoc}
   */
  public function help() {
    return $this->t('Check non-security updates for modules.');
  }

}
