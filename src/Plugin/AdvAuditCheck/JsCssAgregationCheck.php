<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;

use Drupal\Core\Link;
use Drupal\Core\Url;
/**
 * @AdvAuditCheck(
 *   id = "js_css_agregation",
 *   label = @Translation("Javascript & CSS aggregation"),
 *   category = "performance",
 *   requirements = {},
 *   enabled = false,
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

    $status = AuditResultResponseInterface::RESULT_PASS;

    if (!$css_preprocess || !$js_preprocess) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
    }

    $link = Link::fromTextAndUrl('Advanced CSS/JS Aggregation', Url::fromUri('https://www.drupal.org/project/advagg'));
    $params = ['@link' => $link->toString()];

    return new AuditReason($this->id(), $status, $params);
  }

}
