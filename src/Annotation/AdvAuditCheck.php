<?php

namespace Drupal\adv_audit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Advances audit check item annotation object.
 *
 * @see \Drupal\adv_audit\Plugin\AdvAuditCheckManager
 * @see plugin_api
 *
 * @Annotation
 */
class AdvAuditCheck extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The test category if from defined config.
   *
   * @var string
   */
  public $category;

  /**
   * Default importance level.
   *
   * @var string
   */
  public $severity;

  /**
   * Determine plugin requirements.
   *
   * @var array
   */
  public $requirements = [];

  /**
   * Default status of plugin.
   *
   * @var boolean
   */
  public $enabled;

}
