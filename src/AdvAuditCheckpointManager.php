<?php

namespace Drupal\adv_audit;

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
      'Drupal\adv_audit\AdvAuditCheckpointInterface',
      'Drupal\adv_audit\Annotation\AdvAuditCheckpointAnnotation'
    );
    $this->alterInfo('adv_audit_info');
    $this->setCacheBackend($cache_backend, 'adv_audit_info_plugins');
  }

  /**
   * Return plugins by category.
   */
  public function getAdvAuditPlugins($status = 'all') {
    $plugins = [];
    $state = \Drupal::state();
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin) {
      $key = 'adv_audit.' . $plugin['id'];
      $plugin['info'] = ($info = $state->get($key)) ? $info : $plugin;
      if ($status == 'all' || $plugin['info']['status'] == $status) {
        $plugins[$plugin['category']][$plugin['id']] = $plugin;
      }
    }
    return $plugins;
  }

}
