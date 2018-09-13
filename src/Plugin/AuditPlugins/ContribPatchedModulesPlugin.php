<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Link;
use Drupal\hacked\Controller\HackedController;

/**
 * Check Contrib module and Core for patches.
 *
 * @AuditPlugins(
 *   id = "patched_modules_check",
 *   label = @Translation("Patched modules."),
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
class ContribPatchedModules extends AuditBasePlugin {

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $issue_details = [];
    $hacked = new HackedController();
    $hacked = $hacked->hackedStatus();

    $issue_details['hacked_modules'] = [];
    foreach ($hacked['#data'] as $project) {
      if ($project['counts']['different'] != 0 && $project['project_type'] == 'module') {
        $issue_details['hacked_modules'][] = $project;
      }
    }

    if (!empty($issue_details['hacked_modules'])) {
      $issues = [];

      foreach ($issue_details['hacked_modules'] as $hacked_module) {
        $issues = [
          'patched_modules_check_' . $hacked_module['project_name'] => [
            '@issue_title' => 'Changed module: @changed_module',
            '@changed_module' => $hacked_module['title']
          ],
        ];
      }

      return $this->fail('', ['issues' => $issues]);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();

    $hacked = new HackedController();
    $hacked = $hacked->hackedStatus();
    $is_validated = is_array($hacked) && isset($hacked['#data']);

    if (!$is_validated) {
      $link = Link::createFromRoute('here', 'hacked.report')->toString();
      throw new RequirementsException(
        $this->t('Hacked report is not generated. You can generate it %link', ['%link' => $link]),
        $this->pluginDefinition['requirements']['module']
      );
    }
  }

}
