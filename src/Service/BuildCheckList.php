<?php

namespace Drupal\adv_audit\Service;

use Drupal\adv_audit\CheckpointManager;

/**
 * Build and store checklist.
 */
class BuildCheckList {

  protected $check;
  protected $config;

  /**
   * BuildCheckList constructor.
   */
  public function __construct() {
    $this->categories = \Drupal::config('adv_audit.config')
      ->get('adv_audit_settings')['categories'];
  }

  /**
   * Get checkpoint for category.
   *
   * @param string $name
   *   Consist category key.
   *
   * @return array
   *   Return array with data for defined check category.
   */
  public function get($name) {
    if (!isset($this->categories[$name])) {
      \Drupal::logger('adv_audit')
        ->notice('Wrong category key "@category_key".', ['@category_key' => $name]);
      return [];
    }
    return \Drupal::state()->get('adv_audit.checkpoints.' . $name, []);
  }

  /**
   * Rebuild checkpoints.
   */
  public function rebuild() {
    $children = [];
    foreach (get_declared_classes() as $class) {
      if ($class instanceof CheckpointManager) {
        $children[] = $class;
      }
    }
    return $children;
  }

}
