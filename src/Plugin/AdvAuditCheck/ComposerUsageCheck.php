<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

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
class ComposerUsageCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {

    if (file_exists(DRUPAL_ROOT . '/composer.json') && file_exists(DRUPAL_ROOT . '/composer.lock')) {

      return $this->success();
    }

    return $this->fail(NULL);
  }

}
