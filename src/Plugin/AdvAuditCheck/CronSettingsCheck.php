<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $cron_config = $this->configFactory->get('system.cron');
    // Cron warning threshold defaults to two days.
    $threshold_warning = $cron_config->get('threshold.requirements_warning');
    // Cron error threshold defaults to two weeks.
    $threshold_error = $cron_config->get('threshold.requirements_error');

    // Determine when cron last ran.
    $cron_last = $this->state->get('system.cron_last');
    if (!is_numeric($cron_last)) {
      $cron_last = $this->state->get('install_time', 0);
    }

    // Determine severity based on time since cron last ran.
    $severity = REQUIREMENT_INFO;
    if (REQUEST_TIME - $cron_last > $threshold_error) {
      $severity = REQUIREMENT_ERROR;
    }
    elseif (REQUEST_TIME - $cron_last > $threshold_warning) {
      $severity = REQUIREMENT_WARNING;
    }

    if ($severity == REQUIREMENT_ERROR) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
  }


}
