<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\update\UpdateProcessor;
use Drupal\update\UpdateManagerInterface;

/**
 * Check the Drupal core version and its actuality.
 *
 * @AdvAuditCheck(
 *  id = "drupal_core",
 *  label = @Translation("Drupal core"),
 *  category = "core_and_modules",
 *  severity = "high",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class DrupalCoreCheck extends AdvAuditCheckBase implements AdvAuditCheckInterface, ContainerFactoryPluginInterface {
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
   * @param \Drupal\update\UpdateProcessor $update_processor
   *   The update.processor implementation definition.
   * @param \Drupal\update\UpdateManagerInterface $update_manager
   *   The update.manager implementation definition.
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

    $params = [
      '@version' => $current_version,
      '@recommended_version' => $recommended_version,
      '%link' => Link::fromTextAndUrl($this->t('recommended releases'), Url::fromUri('https://www.drupal.org/project/drupal'))->toString(),
    ];

    if ($current_version !== $recommended_version) {
      $issues['drupal_core'] = [
        '@issue_title' => 'Core version is outdated. Current: @version. Recommended: @recommended_version',
        '@version' => $params['@version'],
        '@recommended_version' => $params['@recommended_version'],
      ];
      return $this->fail(NULL, [
        'issues' => $issues,
        '@version' => $params['@version'],
        '@recommended_version' => $params['@recommended_version'],
        '%link' => $params['%link'],
      ]);
    }

    return $this->success($params);
  }

}
