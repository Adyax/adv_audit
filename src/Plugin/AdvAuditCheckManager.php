<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\adv_audit\Exception\AuditException;
use Drupal\adv_audit\Plugin\AdvAuditCheck\MockPluginCheck;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

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
    foreach ($this->getDefinitions() as $plugin) {
      $list[$plugin['category']][$plugin['id']] = $plugin;
    }
    return $list;
  }

  /**
   * Get list of plugin by selected category.
   *
   * @param string $category_id
   *   The category ID.
   *
   * @return array|mixed
   *   Return list if plugin in selected category.
   */
  public function getPluginsByCategoryFilter($category_id) {
    $list = $this->getPluginsByCategory();
    return isset($list[$category_id]) ? $list[$category_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // In some cases audit plugins can have dependency to non-existent service.
    // This is a normal situation when some service is not available on site for
    // audit plugin.
    // We should catch this exception and try to resolve the problem,
    // for having the opportunity to display plugin in listings.
    try {
      return parent::createInstance($plugin_id, $configuration);
    }
    catch (ServiceNotFoundException $e) {
      if (isset($configuration['audit_execute'])) {
        // Throw our Exception for correct reaction on error.
        throw new AuditException($e->getMessage(), $plugin_id);
      }
      // Save original class for plugin instance.
      $this->definitions[$plugin_id]['original_class'] = $this->definitions[$plugin_id]['class'];
      // Override original class to mock (fake) object.
      $this->definitions[$plugin_id]['class'] = MockPluginCheck::class;
      // Try again create needed plugin instance.
      return parent::createInstance($plugin_id, $configuration);
    }
  }

}
