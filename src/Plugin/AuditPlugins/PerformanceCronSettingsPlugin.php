<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\adv_audit\Plugin\AuditPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin for cron settings check.
 *
 * @AuditPlugin(
 *  id = "cron_settings",
 *  label = @Translation("Cron settings"),
 *  category = "performance",
 *  requirements = {},
 * )
 */
class PerformanceCronSettingsPlugin extends AuditBasePlugin implements AuditPluginInterface, ContainerFactoryPluginInterface {

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
   * Constructs a new PerformanceCronSettings object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */

  /**
   * Constructs a new PerformanceCronSettings object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   State instance.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StateInterface $state) {
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
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
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
    $severity = REQUIREMENT_OK;
    if (REQUEST_TIME - $cron_last > $threshold_error) {
      $severity = REQUIREMENT_ERROR;
    }
    elseif (REQUEST_TIME - $cron_last > $threshold_warning) {
      $severity = REQUIREMENT_WARNING;
    }

    if ($severity != REQUIREMENT_OK) {
      $last_cron_launch = \Drupal::service('date.formatter')
        ->formatTimeDiffSince($cron_last);

      return $this->fail(NULL, [
        'issues' => [
          'cron_settings' => [
            '@issue_title' => 'There are problems with cron launch. Last run @time ago.',
            '@time' => $last_cron_launch,
          ],
        ],
        '%link' => Link::createFromRoute($this->t('cron settings page'), 'system.cron_settings')
          ->toString(),
        '@time' => $last_cron_launch,
      ]);
    }
    return $this->success();
  }

}
