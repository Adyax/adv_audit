<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Check modules for security updates.
 *
 * @AdvAuditCheck(
 *   id = "modules_security_check",
 *   label = @Translation("Modules security updates"),
 *   category = "core_and_modules",
 *   requirements = {},
 *   enabled = true,
 *   severity = "critical"
 * )
 */
class ModulesSecurityCheck extends ModulesCheckBase implements AdvAuditCheckInterface, ContainerFactoryPluginInterface {
  /**
   * {@inheritdoc}
   */
  const CHECK_FOR_SECURITY_UPDATES = TRUE;

  /**
   * ModulesSecurityCheck constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\update\UpdateManagerInterface $update_manager
   *   Update manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, UpdateManagerInterface $update_manager, ModuleHandlerInterface $module_handler) {
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
