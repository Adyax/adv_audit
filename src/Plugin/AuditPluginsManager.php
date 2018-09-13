<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\adv_audit\AuditExecutable;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Plugin\AuditPlugins\MockPluginPlugin;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Provides the Advances audit check plugin manager.
 */
class AuditPluginsManager extends DefaultPluginManager {

  /**
   * Constructs a new AuditPluginsManager object.
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
    parent::__construct('Plugin/AuditPlugins', $namespaces, $module_handler, 'Drupal\adv_audit\Plugin\AuditPluginInterface', 'Drupal\adv_audit\Annotation\AuditPlugin');

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
    catch (PluginNotFoundException| ServiceNotFoundException $e) {
      // If current action context is run test scenarios we should
      // throw the error.
      if (isset($configuration[AuditExecutable::AUDIT_EXECUTE_RUN])) {
        $requirements = [];
        if ($e instanceof ServiceNotFoundException) {
          $requirements = ['service' => $e->getSourceId()];
        }
        // Throw our RequirementsException for correct reaction on error.
        throw new RequirementsException($e->getMessage(), $requirements);
      }
      // Save original class for plugin instance.
      $this->definitions[$plugin_id]['original_class'] = $this->definitions[$plugin_id]['class'];
      // Override original class to mock (fake) object.
      $this->definitions[$plugin_id]['class'] = MockPluginPlugin::class;
      // Try again create needed plugin instance.
      return parent::createInstance($plugin_id, $configuration);
    }
  }

}
