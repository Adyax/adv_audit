<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Component\Utility\OpCodeCache;

/**
 * Checks Opcache enabled.
 *
 * @AuditPlugin(
 *   id = "opcache_check",
 *   label = @Translation("Check Opcache"),
 *   category = "server_configuration",
 *   requirements = {},
 * )
 */
class ServerOPCachePlugin extends AuditBasePlugin {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $opcache_enabled = OpCodeCache::isEnabled();
    if ($opcache_enabled) {
      return $this->success();
    }

    return $this->fail(NULL, [
      'issues' => [
        'opcache_check' => [
          '@issue_title' => 'Opcache is disabled.',
        ],
      ],
    ]);
  }

}
