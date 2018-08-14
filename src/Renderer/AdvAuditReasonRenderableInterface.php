<?php

namespace Drupal\adv_audit\Renderer;

use Drupal\adv_audit\AuditReason;

/**
 * Interface AdvAuditReasonRenderableInterface.
 *
 * Defines an object which can be rendered by the Render API.
 *
 * @package Drupla\adv_audit\Renderer
 */
interface AdvAuditReasonRenderableInterface {

  /**
   * Build personalized theming from audit response object.
   *
   * Needed to customize messages for audit report UI.
   *
   * @param \Drupal\adv_audit\AuditReason $reason
   *   The saved AuditReason object.
   * @param string $type
   *   Type of current build process.
   *    See in AuditMessagesStorageInterface::MSG_TYPE_*
   *
   * @return array
   *   Returns a render array representation of the message.
   */
  public function auditReportRender(AuditReason $reason, $type);

}
