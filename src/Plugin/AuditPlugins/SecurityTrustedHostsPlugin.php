<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

use Drupal\Core\Site\Settings;

/**
 * Trusted Host Check plugin class.
 *
 * @AuditPlugin(
 *   id = "trusted_host_check",
 *   label = @Translation("Trusted Host Settings"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class SecurityTrustedHostsPlugin extends AuditBasePlugin {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $trusted_host_patterns = Settings::get('trusted_host_patterns');

    if (empty($trusted_host_patterns)) {
      return $this->fail(NULL, [
        'issues' => [
          'trusted_host_check' => [
            '@issue_title' => 'Trusted hosts param is empty.'
          ],
        ],
      ]);
    }

    return $this->success();
  }

}
