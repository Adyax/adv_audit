<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provide plugin manager for Advanced Audit checkpoints.
 *
 * @package Drupal\adv_audit
 */
class AdvAuditCheckpointManager extends DefaultPluginManager {

  /**
   * CheckpointManager constructor.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/AdvAuditCheckpoint',
      $namespaces,
      $module_handler,
      'Drupal\adv_audit\Plugin\AdvAuditCheckpointInterface',
      'Drupal\adv_audit\Annotation\AdvAuditCheckpointAnnotation'
    );
    $this->alterInfo('adv_audit_info');
    $this->setCacheBackend($cache_backend, 'adv_audit_info_plugins');
  }

}
