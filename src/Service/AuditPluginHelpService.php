<?php

namespace Drupal\adv_audit\Service;

use Drupal\adv_audit\Plugin\AuditPluginsManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provide auditor help information.
 */
class AuditPluginHelpService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The audit plugin service.
   *
   * @var \Drupal\adv_audit\Plugin\AuditPluginsManager
   *   Storage for AuditPluginsManager instance.
   */
  protected $pluginManager;

  /**
   * AuditPluginHelpService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Service instance.
   * @param \Drupal\adv_audit\Plugin\AuditPluginsManager $plugin_manager
   *   Provide access to auditor plugins.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AuditPluginsManager $plugin_manager) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $config_factory;
  }

  /**
   * Return plugins config key.
   *
   * @param $plugin_id
   *   Id from plugin definition.
   *
   * @return string
   *   Plugin's config name.
   */
  private function getConfigKey($plugin_id) {
    return 'adv_audit.plugins.' . $plugin_id;
  }

  /**
   * Get plugins and categories.
   *
   * @return array
   *   Return plugins grouped by category.
   */
  public function getPlugins() {
    $plugins = [];
    return $plugins;
  }

  protected function getHelp($plugin_id){

  }

}
