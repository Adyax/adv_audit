<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Performance modules status plugin class.
 *
 * @AdvAuditCheck(
 *  id = "performance_modules_status",
 *  label = @Translation("Performance modules status"),
 *  category = "performance",
 *  severity = "high",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class PerformanceModulesStatusCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

  /**
   * List of modules to check.
   */
  const PERFORMANCE_MODULES = [
    'dblog',
    'devel',
    'views_ui',
    'page_manager_ui',
  ];

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new CronSettingsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $enabled_modules = [];
    foreach (self::PERFORMANCE_MODULES as $module_name) {
      if ($this->moduleHandler->moduleExists($module_name)) {
        $module_info = system_get_info('module', $module_name);
        $enabled_modules[$module_name] = $module_info['name'];
      }
    }

    if (!empty($enabled_modules)) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, NULL, $enabled_modules);
    }

    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    $build = [];
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      $build['enabled_performance_modules'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Listed modules should be disabled:'),
        '#list_type' => 'ol',
        '#items' => $reason->getArguments(),
      ];
    }
    return $build;
  }

}
