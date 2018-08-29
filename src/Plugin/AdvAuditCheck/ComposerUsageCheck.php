<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if the project uses composer.
 *
 * @AdvAuditCheck(
 *   id = "composer_usage",
 *   label = @Translation("Check if composer is used on the project."),
 *   category = "architecture_analysis",
 *   severity = "high",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class ComposerUsageCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    // TODO: Implement perform() method.
  }

}
