<?php

namespace Drupal\adv_audit\Controller;

//use Mpdf\Mpdf;
//use Mpdf\Output\Destination;
use Drupal\adv_audit\Entity\IssueEntity;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * The class of the Pdf generation' controller.
 */
class AdvAuditIssueStatusController {

//  /**
//   * The Audit Issue storage.
//   *
//   * @var \Drupal\Core\Entity\EntityStorageInterface
//   */
//  protected $IssueEntityStorage;
//
//  /**
//   * Constructs a new IssueEntityRevisionRevertForm.
//   *
//   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
//   *   The Audit Issue storage.
//   */
//  public function __construct(EntityStorageInterface $entity_storage) {
//    $this->IssueEntityStorage = $entity_storage;
//  }

  /**
   * Public function view.
   */
  public function ajaxLinkCallback($issue) {

    $user_id = \Drupal::currentUser()->id();
    $statuses =  IssueEntity::getStatuses();
    $current_status = $issue->status->value;
    $available_status = array_diff($statuses, [$current_status => $current_status]);
    $new_status = array_values($available_status)[0];

    // Make this change a new revision
    $issue->status->value = $new_status;
    $issue->setNewRevision();
    $issue->setRevisionLogMessage(t('Change status from @oldstatus to @newstatus', array('@oldstatus' => $current_status, '@newstatus' => $new_status)));
    $issue->setRevisionCreationTime(REQUEST_TIME);
    $issue->setRevisionUserId($user_id);
    $issue->save();

    # New response
    $response = new AjaxResponse();

    # Commands Ajax
    $response->addCommand(new AlertCommand('Hello ' .$adv_audit_issue));

    # Return response
    return $response;
  }

}
