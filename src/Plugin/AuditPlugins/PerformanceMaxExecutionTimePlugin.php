<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * Check PHP `max_execution_time`.
 *
 * @AuditPlugin(
 *   id = "php_max_execution_time",
 *   label = @Translation("Checking php max_execution_time setting"),
 *   category = "performance",
 *   requirements = {},
 * )
 */
class PerformanceMaxExecutionTimePlugin extends AuditBasePlugin {

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $time = intval(ini_get('max_execution_time'));

    if ($time > 300 || $time === 0) {
      return $this->fail(NULL, [
        'issues' => [
          'php_max_execution_time' => [
            '@issue_title' => 'Max execution time is too high (@time).',
            '@time' => $time,
          ],
        ],
      ]);
    }

    return $this->success();
  }

}
