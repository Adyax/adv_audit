<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\hacked\Controller\HackedController;

/**
 * Check Drupal Core for patches.
 *
 * @AuditPlugin(
 *   id = "patched_core",
 *   label = @Translation("Patched Drupal core."),
 *   category = "core_and_modules",
 *   requirements = {
 *     "module": {
 *      "hacked:2.0-beta",
 *     },
 *   },
 *   severity = "high"
 * )
 */
class ContribPatchedCorePlugin extends ContribPatchedModulesPlugin {

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $hacked = new HackedController();
    $hacked = $hacked->hackedStatus();

    foreach ($hacked['#data'] as $project) {
      if ($project['counts']['different'] != 0 && $project['project_type'] == 'core') {
        $issues['hacked_core'] = [
          '@issue_title' => '@title was hacked (@count changes)',
          '@title' => $project['title'],
          '@count' => $project['counts']['different'],
        ];
        return $this->fail('', ['issues' => $issues]);
      }
    }

    return $this->success();
  }

}
