<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\SystemManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * @AdvAuditCheck(
 *  id = "cron_settings",
 *  label = @Translation("Cron settings"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class CronSettingsCheck extends AdvAuditCheckBase implements  AdvAuditCheckInterface, ContainerFactoryPluginInterface {

  /**
   * Length of the day in seconds.
   */
  const DAYTIMESTAMP = 86400;

  /**
   * Drupal\system\SystemManager definition.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SystemManager $system_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->systemManager = $system_manager;
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
      $container->get('system.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $requirements = $this->systemManager->listRequirements();
    $params = [
      'last_run' => $requirements['cron'],
    ];

    $adv_cron = FALSE;
    foreach (['ultimate_cron'] as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        $adv_cron = TRUE;
        $params['adv_module'] = $module;
        break;
      }
    }

    if (!$adv_cron || isset($requirements['cron']['severity'])) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, AuditMessagesStorageInterface::MSG_TYPE_FAIL);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS, AuditMessagesStorageInterface::MSG_TYPE_SUCCESS);
  }


}
