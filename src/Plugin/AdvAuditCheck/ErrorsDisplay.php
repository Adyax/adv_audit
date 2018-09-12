<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

/**
 * Provide errors display check.
 *
 * @AdvAuditCheck(
 *  id = "errors_display",
 *  label = @Translation("Error are written to the screen."),
 *  category = "security",
 *  severity = "normal",
 *  enabled = true,
 *  requirements = {},
 * )
 */
class ErrorsDisplay extends AdvAuditCheckBase {

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
