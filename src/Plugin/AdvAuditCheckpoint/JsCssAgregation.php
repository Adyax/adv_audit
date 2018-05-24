<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Annotation\AdvAuditCheckpointAnnotation;

/**
 * Class JsCssAgregation
 *   Check if agregation for js and css is enabled.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = 'js_css_agregation',
 *   label = @Translation('Javascript & CSS aggregation'),
 *   category = 'performance',
 *   status = true,
 *   severity = 'high'
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class JsCssAgregation {

}