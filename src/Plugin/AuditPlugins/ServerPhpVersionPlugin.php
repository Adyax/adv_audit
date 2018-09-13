<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * PHP Version Check plugin class.
 *
 * @AuditPlugins(
 *   id = "php_version_check",
 *   label = @Translation("PHP Version"),
 *   category = "server_configuration",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class ServerPhpVersionPlugin extends AuditBasePlugin {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $phpversion = phpversion();

    if (version_compare($phpversion, DRUPAL_RECOMMENDED_PHP) < 0) {
      return $this->fail(NULL, [
        'issues' => [
          'php_version_check' => [
            '@issue_title' => 'Current version is @current_v. Recommended version is @recommended_v',
            '@current_v' => $phpversion,
            '@recommended_v' => DRUPAL_RECOMMENDED_PHP,
          ],
        ],
        '%recommended' => DRUPAL_RECOMMENDED_PHP
      ]);
    }

    return $this->success();
  }

}
