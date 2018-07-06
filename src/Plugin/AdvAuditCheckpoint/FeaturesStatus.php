<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\features\Package;
use Drupal\features\FeaturesManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * Check non-security updates for contrib modules.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "features_status_check",
 *   label = @Translation("Features status"),
 *   description = @Translation("Check features status."),
 *   category = "core_and_modules",
 *   status = TRUE,
 *   severity = "low"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class FeaturesStatus extends AdvAuditCheckpointBase {

  /**
   * {@inheritdoc}
   */
  public $actionMessage = 'It is recommended to update features with the latest structure and settings done in the BO.';

  /**
   * {@inheritdoc}
   */
  public $impactMessage = 'statuses “overridden”, “needs review” and “rebuilding” must be avoided because it means that configurations that are defined in the feature module\'s code have been overridden in the Drupal backend by a user and during next execution of features revert execution (that is a part of any normal Continuous Deployment process) these changes will be lost.';

  /**
   * {@inheritdoc}
   */
  public $failMessage = 'There are non-actual features on web-site.';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $container);
    if ($this->validation()) {
      $this->additionalServices = [
        'featuresManager' => 'features.manager',
        'assigner' => 'features_assigner',
      ];
      // Add additional services.
      foreach ($this->additionalServices as $varname => $service) {
        $this->{$varname} = $container->get($service);
      }
    }
  }

  /**
   * Return description of current checkpoint.
   *
   * @return mixed
   *   Associated array.
   */
  public function getDescription($params = []) {
    return $this->t("Features provides a centralized place to manage, configure and export components and write them to code. This convenience makes Features an excellent tool for managing changes (for Drupal7) between development environments and version control in conjunction with Git, SVN, or other version control systems.");
  }

  /**
   * Process checkpoint review.
   */
  public function process() {
    $current_bundle = $this->assigner->getBundle();
    $this->assigner->assignConfigPackages();
    $packages = $this->featuresManager->getPackages();
    $config_collection = $this->featuresManager->getConfigCollection();
    $this->addUnpackaged($packages, $config_collection);
    $packages = $this->featuresManager->filterPackages($packages, $current_bundle->getMachineName());
    foreach ($packages as $package) {
      if ($package->getStatus() == FeaturesManagerInterface::STATUS_INSTALLED && $this->featuresManager->detectOverrides($package, TRUE)) {
        $this->setProcessStatus($this::FAIL);
        break;
      }
    }

    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->getDescription(),
      'information' => $this->getProcessResult(),
      'status' => $this->getProcessStatus(),
      'severity' => $this->get('severity'),
      'actions' => $this->getActions(),
      'impacts' => $this->getImpacts(),
    ];

    $results[$this->get('category')][$this->getPluginId()] = $result;
    return $results;
  }

  /**
   * Adds a pseudo-package to display unpackaged configuration.
   *
   * @param array $packages
   *   An array of package names.
   * @param \Drupal\features\ConfigurationItem[] $config_collection
   *   A collection of configuration.
   */
  protected function addUnpackaged(array &$packages, array $config_collection) {
    $packages['unpackaged'] = new Package('unpackaged', [
      'name' => $this->t('Unpackaged'),
      'description' => $this->t('Configuration that has not been added to any package.'),
      'config' => [],
      'status' => FeaturesManagerInterface::STATUS_NO_EXPORT,
      'version' => '',
    ]);
    foreach ($config_collection as $item_name => $item) {
      if (!$item->getPackage() && !$item->isExcluded() && !$item->isProviderExcluded()) {
        $packages['unpackaged']->appendConfig($item_name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return $this->t('Check if all Features are actual.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequirements() {
    return ['modules' => ['features']];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface &$form_state) {
    parent::settingsForm($form, $form_state);
    if (!$this->validation()) {
      $form['status']['#attributes']['disabled'] = TRUE;
    }
  }

}
