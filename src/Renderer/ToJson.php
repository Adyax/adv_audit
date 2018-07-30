<?php

namespace Drupal\adv_audit\Renderer;

use Drupal\adv_audit\Renderer;
use Drupal\Component\Serialization\Json;

/**
 * Class ToJson.
 *
 * @package Drupal\adv_audit\Renderer
 */
class ToJson extends Renderer {

  /**
   * Render function.
   *
   * @return string
   *   Return json format of audit results.
   */
  public function render() {
    return Json::encode($this->reports);
  }

}
