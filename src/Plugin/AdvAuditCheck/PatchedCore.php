<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\hacked\Controller\HackedController;

/**
 * Check Drupal Core for patches.
 *
 * @AdvAuditCheck(
 *   id = "patched_core",
 *   label = @Translation("Patched Drupal core."),
 *   category = "core_and_modules",
 *   requirements = {
 *     "module": {
 *      "hacked:2.0-beta",
 *     },
 *   },
 *   enabled = true,
 *   severity = "high"
 * )
 */
class PatchedCore extends PatchedModulesCheck {

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $hacked = new HackedController();
    $hacked = $hacked->hackedStatus();

    $issues = [];
    foreach ($hacked['#data'] as $project) {
      if ($project['counts']['different'] != 0 && $project['project_type'] == 'core') {
        $issues['hacked_core'] = [
          '@issue_title' => '@title was hacked (@count changes)',
          '@title' => $project['title'],
          '@count' => $project['counts']['different'],
        ];
        break;
      }
    }

    if (!empty($issues)) {
      return $this->fail('', ['issues' => $issues]);
    }

    return $this->success();
  }

}
