<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * Class MockPluginPlugin
 *
 * Used to override the original plug-in class when there is a problem with nonexistent services.
 *
 * @see \Drupal\adv_audit\Plugin\AuditPluginsManager::createInstance().
 *
 * @package Drupal\adv_audit\Plugin\AuditPlugins
 */
class MockPluginPlugin extends AuditBasePlugin {

  /**
   * Constructs a new MockPluginPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
  }


}
