<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * Provide errors display check.
 *
 * @AuditPlugins(
 *  id = "errors_display",
 *  label = @Translation("Error are written to the screen."),
 *  category = "security",
 *  severity = "normal",
 *  enabled = true,
 *  requirements = {},
 * )
 */
class SecurityErrorsDisplay extends AuditBasePlugin {

  /**
   * The actual procedure of carrying out the check.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function perform() {
    $config = $this->config('system.logging');
    $display = $config->get('error_level');

    if ($display != 'hide') {
      return $this->fail(NULL, [
        'issues' => [
          'errors_display' => [
            '@issue_title' => 'Errors are displayed!',
          ],
        ],
      ]);
    }

    return $this->success();
  }

}
