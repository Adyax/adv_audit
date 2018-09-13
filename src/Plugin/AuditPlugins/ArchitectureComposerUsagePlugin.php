<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * Check if the project uses composer.
 *
 * @AuditPlugin(
 *   id = "composer_usage_check",
 *   label = @Translation("Check if composer is used on the project."),
 *   category = "architecture_analysis",
 *   severity = "high",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class ArchitectureComposerUsagePlugin extends AuditBasePlugin {

  /**
   * {@inheritdoc}
   */
  public function perform() {

    if (file_exists(DRUPAL_ROOT . '/../composer.json') && file_exists(DRUPAL_ROOT . '/../composer.lock')) {

      return $this->success();
    }

    return $this->fail(NULL, [
      'issues' => [
        'composer_usage_check' => [
          '@issue_title' => 'There is no composer files in ROOT directory of DrupalProject.',
        ],
      ],
    ]);
  }

}
