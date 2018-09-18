<?php

namespace Drupal\adv_audit\Service;

/**
 * Interface AdvAuditPluginConfigStorageServiceInterface.
 */
interface AuditPluginConfigStorageServiceInterface {

  /**
   * Get value from config storage.
   *
   * @param string $key
   *   The config name.
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
   * @param string $key
   *   The settings key.
   * @param mixed $value
   *   The value for save.
   */
  public function set($key, $value);

  /**
   * Set the current plugin id.
   *
   * @param string $plugin_id
   *   The plugin ID.
   */
  public function setPluginId($plugin_id);

}
