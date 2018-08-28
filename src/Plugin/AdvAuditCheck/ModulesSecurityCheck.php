<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * @AdvAuditCheck(
 *   id = "modules_security_check",
 *   label = @Translation("Modules security updates"),
 *   category = "core_and_modules",
 *   requirements = {},
 *   enabled = true,
 *   severity = "critical"
 * )
 */
class ModulesSecurityCheck extends AdvAuditModulesCheckBase implements AdvAuditCheckInterface, ContainerFactoryPluginInterface {
  /**
   * {@inheritdoc}
   */
  const CHECK_FOR_SECURITY_UPDATES = TRUE;

  /**
   * Constructs a new ModulesSecurityCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
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
