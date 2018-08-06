<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Advances audit check plugin manager.
 */
class AdvAuditCheckManager extends DefaultPluginManager {


  /**
   * Constructs a new AdvAuditCheckManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AdvAuditCheck', $namespaces, $module_handler, 'Drupal\adv_audit\Plugin\AdvAuditCheckInterface', 'Drupal\adv_audit\Annotation\AdvAuditCheck');

    $this->alterInfo('adv_audit_check_info');
    $this->setCacheBackend($cache_backend, 'adv_audit_check_plugins');
  }

  /**
   * Build plugins list by category.
   *
   * @return array
   *   list of available plugin by category.
   */
  public function getPluginsByCategory() {
    $list = [];
    foreach($this->getDefinitions() as $plugin) {
      $list[$plugin['category']][$plugin['id']] = $plugin;
    }
    return $list;
  }

}
