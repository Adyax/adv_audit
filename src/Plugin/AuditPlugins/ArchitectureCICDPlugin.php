<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * Check if CI/CD exists on the project.
 *
 * @AuditPlugin(
 *   id = "ci_cd_check",
 *   label = @Translation("Check if CI/CD exists on the project"),
 *   category = "architecture_analysis",
 *   requirements = {},
 * )
 */
class ArchitectureCICDPlugin extends AuditBasePlugin {

  /**
   * {@inheritdoc}
   */
  public function perform() {

    if (file_exists(DRUPAL_ROOT . '/.gitlab-ci.yml') || file_exists(DRUPAL_ROOT . '/../.gitlab-ci.yml')) {

      return $this->success();
    }

    return $this->fail(NULL, [
      'issues' => [
        'ci_cd_check' => [
          '@issue_title' => 'There is no GITlab CI/CD on the project',
        ],
      ],
    ]);
  }

}
