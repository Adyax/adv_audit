<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\features\FeaturesAssigner;
use Drupal\features\FeaturesManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if Features are overridden.
 *
 * @AdvAuditCheck(
 *   id = "features_status_check",
 *   label = @Translation("Features status"),
 *   category = "core_and_modules",
 *   severity = "low",
 *   requirements = {
 *     "module": {
 *       "features",
 *     },
 *   },
 *   enabled = TRUE,
 * )
 */
class FeaturesStatusCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

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
      return $this->fail(NULL, $overridden_packages);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      $build['features_list_fails'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Failed features'),
        '#list_type' => 'ol',
        '#items' => $reason->getArguments(),
      ];
      return $build;
    }
    return [];
  }

}
