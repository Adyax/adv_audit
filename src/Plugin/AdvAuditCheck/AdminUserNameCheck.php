<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

/**
 * Plugin for check Administrator's name.
 *
 * @AdvAuditCheck(
 *   id = "admin_name_check",
 *   label = @Translation("Administrator's name check"),
 *   category = "security",
 *   severity = "low",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class AdminUserNameCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    global $base_url;

    // Get admin's name.
    $user = \Drupal::entityTypeManager()->getStorage('user')->load(1);
    $adminNAme = $user->get('name')->value;
    $secure = TRUE;
    $arguments = [];

    $arguments['admin_name'] = $adminNAme;

    // Get host.
    $parsed_base = parse_url($base_url);
    $host_parts = explode('.', $parsed_base['host']);
    $name_contains_host_part = FALSE;
    foreach ($host_parts as $part) {
      if (stripos($adminNAme, $part) !== FALSE) {
        $name_contains_host_part = TRUE;
        $arguments['has_host_parts'][] = $part;
      }
    }

    // The username contains "admin".
    if (stripos($adminNAme, 'admin') !== FALSE || $name_contains_host_part) {
      $secure = FALSE;
      $arguments['has_admin_parts'] = TRUE;
    }

    if ($adminNAme == 'admin') {
      $arguments['has_default_admin_name'] = TRUE;
    }

    if (!$secure) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, NULL, $arguments);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
  }

}
