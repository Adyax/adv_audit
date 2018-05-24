<?php

namespace Drupal\adv_audit;

use Drupal\Component\Plugin\Factory\DefaultFactory;
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
   *
   * @param \Traversable $namespaces
   * @param \Drupal\adv_audit\CacheBackendInterface $cache_backend
   * @param \Drupal\adv_audit\ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/AdvAuditCheckpoint',
      $namespaces,
      $module_handler,
      'Drupal\adv_audit\AdvAuditCheckpointInterface',
      'Drupal\adv_audit\Annotation\AdvAuditCheckpointAnnotation'
    );
    $this->alterInfo('adv_audit_info');
    $this->setCacheBackend($cache_backend, 'adv_audit_info_plugins');
  }
}
