<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Advances audit check plugins.
 */
abstract class AdvAuditCheckBase extends PluginBase implements AdvAuditCheckInterface {

  // Add common methods and abstract methods for your plugin type here.

  public function getMessage($type) {

  }

  public function getCategoryName() {}

}
