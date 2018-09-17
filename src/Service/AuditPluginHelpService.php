<?php

namespace Drupal\adv_audit\Service;

use Drupal\adv_audit\Plugin\AuditPluginsManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provide auditor help information.
 */
class AuditPluginHelpService {

  use StringTranslationTrait;

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
   * Audit categories service.
   *
   * @var \Drupal\adv_audit\Service\AuditCategoryManagerService
   *   Provide access to audit categories.
   */
  protected $categoryManager;

  /**
   * AuditPluginHelpService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Service instance.
   * @param \Drupal\adv_audit\Plugin\AuditPluginsManager $plugin_manager
   *   Provide access to auditor plugins.
   * @param \Drupal\adv_audit\Service\AuditCategoryManagerService $category_manager
   *   Access to category manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AuditPluginsManager $plugin_manager, AuditCategoryManagerService $category_manager) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $plugin_manager;
    $this->categoryManager = $category_manager;
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
  public function getHelp() {
    $categories = $this->pluginManager->getPluginsByCategory();
    ksort($categories);
    $render_array = [];
    foreach ($categories as $key => $category) {
      $category_definition = $this->categoryManager->getCategoryDefinition($key);
      $render_array[$key]['title'] = $category_definition['label'];
      $render_array[$key]['plugins'] = $this->render($category);
    }
    return $render_array;
  }

  protected function render($category) {
    foreach ($category as &$plugin) {
      $plugin = [
        'label' => $this->t($plugin['label']->__toString()),
        'help' => $this->t($this->getPluginHelp($plugin['id'])),
      ];
    }
    return $category;
  }

  protected function getPluginHelp($plugin_id) {
    $configs = $this->configFactory->get($this->getConfigKey($plugin_id))
      ->getRawData();
    return isset($configs['help']) ? $configs['help'] : $this->t('Plugin has\'not help information.')->__toString();
  }

}
