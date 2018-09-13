<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

/**
 * Check if CSS/JS aggregation is enabled.
 *
 * @AuditPlugins(
 *   id = "js_css_agregation",
 *   label = @Translation("Javascript & CSS aggregation"),
 *   category = "performance",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class PerformanceJsCssAgregation extends AuditBasePlugin {

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $css_preprocess = $this->configFactory()->get('system.performance')->get('css.preprocess');
    $js_preprocess = $this->configFactory()->get('system.performance')->get('js.preprocess');

    $issues = [];
    if (!$css_preprocess) {
      $issues['css_aggregation_disabled'] = [
        '@issue_title' => 'CSS aggregation disabled.'
      ];
    }
    if (!$js_preprocess) {
      $issues['js_aggregation_disabled'] = [
        '@issue_title' => 'Javascript aggregation disabled.'
      ];
    }

    if (!empty($issues)) {
      return $this->fail(NULL, ['issues' => $issues]);
    }

    return $this->success();
  }

}
