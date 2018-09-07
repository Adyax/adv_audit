<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

/**
 * Check if CSS/JS aggregation is enabled.
 *
 * @AdvAuditCheck(
 *   id = "js_css_agregation",
 *   label = @Translation("Javascript & CSS aggregation"),
 *   category = "performance",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class JsCssAgregationCheck extends AdvAuditCheckBase {

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $css_preprocess = $this->configFactory()->get('system.performance')->get('css.preprocess');
    $js_preprocess = $this->configFactory()->get('system.performance')->get('js.preprocess');

    $issue_details = [];
    if (!$css_preprocess) {
      $issue_details['css_aggregation_disabled'] = TRUE;
    }
    if (!$js_preprocess) {
      $issue_details['js_aggregation_disabled'] = TRUE;
    }

    if (!empty($issue_details)) {
      return $this->fail(NULL, $issue_details);
    }

    return $this->success();
  }

}
