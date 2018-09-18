<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * Class MockPlugin.
 *
 * Used to override the original plug-in class when there is a problem
 * with nonexistent services.
 *
 * @see \Drupal\adv_audit\Plugin\AuditPluginsManager::createInstance().
 *
 * @package Drupal\adv_audit\Plugin\AuditPlugins
 */
class MockAuditPlugin extends AuditBasePlugin {

  /**
   * {@inheritdoc}
   */
  public function perform() {
  }

}
