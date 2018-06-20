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
    $state = $this->container->get('state');
    $categories = $this->container->get('config.factory')
      ->get('adv_audit.config')->get('adv_audit_settings')['categories'];
    $definitions = $this->manager->getDefinitions();
    foreach ($definitions as $plugin) {
      $key = 'adv_audit.' . $plugin['id'];
      $plugin['info'] = ($info = $state->get($key)) ? $info : $plugin;
      if ($status == 'all' || ($plugin['info']['status'] == $status && $categories[$plugin['category']]['status'])) {
        $plugins[$plugin['category']][$plugin['id']] = $plugin;
      }
    }
    return $plugins;
  }

}
