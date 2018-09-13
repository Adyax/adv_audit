<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditPluginInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Check modules for non-security updates.
 *
 * @AuditPlugins(
 *   id = "modules_update_check",
 *   label = @Translation("Modules non-security updates"),
 *   category = "core_and_modules",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class ContribModulesUpdate extends ModulesBasePlugin implements AuditPluginInterface, ContainerFactoryPluginInterface {
  /**
   * {@inheritdoc}
   */
  const CHECK_FOR_SECURITY_UPDATES = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UpdateManagerInterface $update_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->updateManager = $update_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('update.manager'),
      $container->get('module_handler')
    );
  }

}
