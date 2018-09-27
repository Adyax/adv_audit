<?php

namespace Drupal\adv_audit\Controller;

use Drupal\adv_audit\Entity\IssueEntity;
use Drupal\Core\Controller\ControllerBase;


/**
 * The class of the Pdf generation' controller.
 */
class AuditIssueStatusController extends ControllerBase {

  /**
   * Public function view.
   */
  public function issueChangeStatus($adv_audit_id, $issue) {
    $user_id = \Drupal::currentUser()->id();
    $statuses = IssueEntity::getStatuses();
    $current_status = $issue->status->value;
    $available_status = array_diff($statuses, [$current_status => $current_status]);
    $new_status = array_values($available_status)[0];

    // Make this change a new revision
    $issue->status->value = $new_status;
    $issue->setNewRevision();
    $issue->setRevisionLogMessage(t('Change status from @oldstatus to @newstatus', [
      '@oldstatus' => $current_status,
      '@newstatus' => $new_status,
    ]));
    $issue->setRevisionCreationTime(REQUEST_TIME);
    $issue->setRevisionUserId($user_id);
    $issue->save();

    // Build message after change status of issue
    $type = ($new_status == IssueEntity::STATUS_IGNORED) ? 'warning' : 'status';
    $message = t('Status of "@issue_name" issue was changed to "@status".', [
      '@issue_name' => $issue->__toString(),
      '@status' => $new_status,
    ]);
    \Drupal::messenger()->addMessage($message, $type);

    return $this->redirect('entity.adv_audit.canonical', ['adv_audit' => $adv_audit_id]);
  }

}
