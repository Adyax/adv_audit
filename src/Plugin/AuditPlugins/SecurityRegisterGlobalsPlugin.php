<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * Check Register Globals is enabled.
 *
 * @AuditPlugins(
 *   id = "register_globals_check",
 *   label = @Translation("PHP register globals"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class SecurityRegisterGlobals extends AuditBasePlugin {

  /**
   * {@inheritdoc}
   */
  public function perform() {

    $register_globals = trim(ini_get('register_globals'));
    if (!empty($register_globals) && strtolower($register_globals) != 'off') {
      $this->fail(NULL, [
        'issues' => [
          'register_globals_check' => [
            '@issue_title' => 'register_globals is enabled.',
          ],
        ],
      ]);
    }

    return $this->success();
  }

}
