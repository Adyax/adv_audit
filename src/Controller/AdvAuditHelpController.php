<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * The class of the Help pages' controller.
 */
class AdvAuditHelpController extends ControllerBase {

  /**
   * Creates the Run & Review page.
   */
  public function index() {
    $output = 'Dummy';

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

}
