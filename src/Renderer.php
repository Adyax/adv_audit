<?php

namespace Drupal\adv_audit;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Renderer.
 */
abstract class Renderer {
  /**
   * The Report to be rendered.
   *
   * @var string
   */
  public $reports;


  use StringTranslationTrait;

  /**
   * Renderer constructor.
   *
   * @param string $reports
   *   Reports to be rendered.
   */
  public function __construct($reports) {
    $this->reports = $reports;
  }

  /**
   * Renderer function.
   *
   * @return mixed
   *   Return rendered values.
   */
  abstract public function render();

}
