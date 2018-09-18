<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\features\FeaturesAssigner;
use Drupal\features\FeaturesManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if Features are overridden.
 *
 * @AuditPlugin(
 *   id = "features_status_check",
 *   label = @Translation("Features status"),
 *   category = "core_and_modules",
 *   requirements = {
 *     "module": {
 *       "features",
 *     },
 *   },
 * )
 */
class ContribFeaturesStatusPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  protected $featuresManager;

  protected $featuresAssigner;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FeaturesManager $features_manager, FeaturesAssigner $features_assigner) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->featuresManager = $features_manager;
    $this->featuresAssigner = $features_assigner;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('features.manager'),
      $container->get('features_assigner')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $current_bundle = $this->featuresAssigner->getBundle();
    $this->featuresAssigner->assignConfigPackages();
    $packages = $this->featuresManager->getPackages();

    $packages = $this->featuresManager->filterPackages($packages, $current_bundle->getMachineName());

    $overridden_packages = [];

    foreach ($packages as $package) {
      if (!empty($this->featuresManager->detectOverrides($package))) {
        $overridden_packages[] = $package->getName();
      }
    }

    if (!empty($overridden_packages)) {
      $issues = [];
      foreach ($overridden_packages as $item) {
        $issues['features_status_check_' . $item] = [
          '@issue_title' => 'Overridden feature package: @package.',
          '@package' => $item,
        ];
      }
      return $this->fail(NULL, ['issues' => $issues]);
    }

    return $this->success();
  }

}
