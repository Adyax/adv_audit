<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\update\UpdateProcessor;
use Drupal\update\UpdateManagerInterface;

/**
 * @AdvAuditCheck(
 *  id = "drupal_core",
 *  label = @Translation("Drupal core"),
 *  category = "core_and_modules",
 *  severity = "high",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class DrupalCoreCheck extends AdvAuditCheckBase implements  AdvAuditCheckInterface, ContainerFactoryPluginInterface {
  /**
   * Project name.
   */
  const PROJECT_NAME = 'drupal';

  /**
   * Drupal\update\UpdateProcessor definition.
   *
   * @var \Drupal\update\UpdateProcessor
   */
  protected $updateProcessor;

  /**
   * Drupal\update\UpdateProcessor definition.
   *
   * @var \Drupal\update\UpdateProcessor
   */
  protected $updateManager;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UpdateProcessor $update_processor, UpdateManagerInterface $update_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->updateProcessor = $update_processor;
    $this->updateManager = $update_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('update.processor'),
      $container->get('update.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    // Check updates for Drupal core.
    $project = [
      'name' => self::PROJECT_NAME,
      'project_type' => 'core',
      'includes' => [],
    ];

    $this->updateProcessor->processFetchTask($project);

    // Set process status 'fail' if current version is not recommended.
    $projects_data = $this->updateManager->projectStorage('update_project_data');
    $current_version = $projects_data[self::PROJECT_NAME]['existing_version'];
    $recommended_version = $projects_data[self::PROJECT_NAME]['recommended'];

    if ($current_version != $recommended_version) {
      return new AuditReason(
        $this->id(), AuditResultResponseInterface::RESULT_FAIL,
        $this->t('Current core version @c_ver differs from recommended version @r_ver', ['@c_ver' => $current_version, '@r_ver' => $recommended_version]),
        ['@version' => $current_version]);
    }
    else {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS, NULL, ['@version' => $current_version]);
    }
  }


}
