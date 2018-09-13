<?php

namespace Drupal\adv_audit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class AdvAuditPluginConfigStorageService.
 */
class AuditPluginConfigStorageService implements AuditPluginConfigStorageServiceInterface {

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The state service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AdvAuditPluginConfigStorageService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Return plugins config key.
   *
   * @return string
   *   Plugin's config name.
   */
  private function getConfigKey() {
    return 'adv_audit.plugins.' . $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key = NULL, $default = NULL) {
    $configs = $this->configFactory->get($this->getConfigKey(), [])
      ->getRawData();
    if (is_null($key)) {
      return isset($configs['settings']) ? $configs['settings'] : $default;
    }
    return isset($configs['settings'][$key]) ? $configs['settings'][$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key = NULL, $value) {
    $configs = $this->configFactory->getEditable($this->getConfigKey());
    $data = $configs->getRawData();
    if (is_null($key)) {
      foreach ($value as $key => $item) {
        $data['settings'][$key] = $item;
      }
    }
    else {
      $data['settings'][$key] = $value;
    }
    $configs->set('settings', $data['settings'])->save();
  }

}
