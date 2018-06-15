<?php

namespace Drupal\adv_audit\Renderer;

use Drupal\adv_audit\Renderer;

/**
 * Class ToHtml.
 *
 * @package Drupal\adv_audit\Renderer
 */
class ToHtml extends Renderer {

  /**
   * The renderer service.
   *
   * @return mixed
   *   Return reports in HTML.
   */
  public function render() {
    $renderer = \Drupal::service('renderer');
    return $renderer->render($this->reports);
  }

}
