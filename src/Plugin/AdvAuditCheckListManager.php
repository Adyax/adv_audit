<?php

namespace Drupal\adv_audit\Plugin;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide plugin manager for Advanced Audit checkpoints.
 *
 * @package Drupal\adv_audit\Plugin
 */
class AdvAuditCheckListManager {

  public $enabled = 1;

  /**
   * AdvAuditCheckListManager constructor.
   */
  public function __construct(AdvAuditCheckpointManager $manager, ContainerInterface $container) {
    $this->manager = $manager;
    $this->container = $container;
  }

  /**
   * Return plugins by category.
   */
  public function getPluginsByStatus($status = 'all') {
    $plugins = [];
    $categories = $this->container->get('config.factory')
      ->get('adv_audit.config')->get('adv_audit_settings')['categories'];
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin) {
      $plugin = $this->manager->createInstance($plugin['id']);
      $plugin_settings = $plugin->getInformation();
      if ($status == 'all' || ($plugin_settings['status'] == $status && $categories[$plugin_settings['category']]['status'])) {
        $plugins[$plugin_settings['category']][$plugin_settings['id']] = $plugin_settings;
      }
    }
    return $plugins;
  }

  /**
   * Return plugins by category.
   */
  public function getHelpPlugins() {
    $plugins = [];
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin) {
      $plugin = $this->manager->createInstance($plugin['id']);
      $category = $plugin->get('category');
      $plugins[$category]['title'] = $plugin->getCategoryTitle();
      $plugins[$category]['plugins'][$plugin->get('id')] = [
        'label' => $plugin->get('label'),
        'help' => $plugin->help(),
      ];
    }
    return $plugins;
  }

  /**
   * Return definitions.
   */
  public function getDefinitions() {
    return $this->manager->getDefinitions();
  }

}
