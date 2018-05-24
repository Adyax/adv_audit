<?php

namespace Drupal\adv_audit\Annotation;

class AdvAuditCheckpointAnnotation {

  /**
   * @var
   *   Plugin machine name.
   */
  public $id;

  /**
   * @var
   *   Plugin human readable name.
   */
  public $label;

  /**
   * @var
   *   Plugin category id @see congig adv_audit.config.
   */
  public $category;

  /**
   * @var
   *   Plugin default status (bool)
   */
  public $status;

  /**
   * @var
   *   Severity level, possible values: 'low', 'normal', 'high', 'critical'.
   */
  public $severity;
}