<?php

namespace Drupal\adv_audit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class AdvAuditCheckpointAnnotation.
 *
 * @package Drupal\adv_audit\Annotation
 */
class AdvAuditCheckpointAnnotation extends Plugin {

  /**
   * Plugin machine name.
   *
   * @var string
   */
  public $id;

  /**
   * Plugin human readable name.
   *
   * @var string
   */
  public $label;

  /**
   * Plugin category id @see congig adv_audit.config.
   *
   * @var string
   */
  public $category;

  /**
   * Plugin default status (bool)
   *
   * @var bool
   */
  public $status = FALSE;

  /**
   * Severity level, possible values: 'low', 'normal', 'high', 'critical'.
   *
   * @var string
   */
  public $severity;

}
