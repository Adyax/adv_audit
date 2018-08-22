<?php

namespace Drupal\adv_audit;

/**
 * Interface AdvAuditPluginConfigStorageServiceInterface.
 */
interface AdvAuditPluginConfigStorageServiceInterface {

  /**
   * Get value from config storage.
   *
   * @param string $key
   *   The config name.
   *
   * @param mixed $default
   *   The default value to return.
   *
   * @return mixed
   *   Return sored value or NULL if settings are not exist.
   */
  public function get($key, $default = NULL);

  /**
   * Set settings value ro storage.
   *
   * @param $key
   *   The settings key.
   * @param $value
   *   The value for save.
   */
  public function set($key, $value);

  /**
   * Set the current plugin id.
   *
   * @param $plugin_id
   *   The plugin ID.
   */
  public function setPluginId($plugin_id);

}
