<?php

namespace Drupal\adv_audit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a AdvAuditCheckpoint plugin item annotation object.
 *
 * @see \Drupal\adv_audit\Plugin\AdvAuditCheckpointAnnotation
 * @see plugin_api
 *
 * @Annotation
 */
class AdvAuditCheckpointAnnotation extends Plugin {

  /**
   * Plugin machine name.
   *
   * @var string
   */
  public $id = '';

  /**
   * Plugin human readable name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * Plugin category id @see congig adv_audit.config.
   *
   * @var string
   */
  public $category = '';

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
  public $severity = 'low';

}
