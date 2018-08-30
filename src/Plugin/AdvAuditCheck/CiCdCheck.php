<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

/**
 * Check if CI/CD exists on the project.
 *
 * @AdvAuditCheck(
 *   id = "ci_cd_check",
 *   label = @Translation("Check if CI/CD exists on the project"),
 *   category = "architecture_analysis",
 *   severity = "normal",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class CiCdCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {

    if (file_exists(DRUPAL_ROOT . '/.gitlab-ci.yml')) {

      return $this->success();
    }

    return $this->fail(NULL);
  }

}
