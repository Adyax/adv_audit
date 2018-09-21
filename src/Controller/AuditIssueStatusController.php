<?php

namespace Drupal\adv_audit\Controller;

use Drupal\adv_audit\Entity\IssueEntity;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * The class of the Pdf generation' controller.
 */
class AuditIssueStatusController {

  /**
   * Public function view.
   */
  public function issueChangeStatus($adv_audit_id, $issue) {

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

    $response = new AjaxResponse();
    $selector = '#adv-audit-report';
    $message_selector = '.region-highlighted';

    $entity_report = \Drupal::entityTypeManager()->getStorage('adv_audit')->load($adv_audit_id);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('adv_audit');
    $build = $view_builder->view($entity_report, 'full');
    $renderer = render($build);
    $response->addCommand(new ReplaceCommand($selector, $renderer, $settings = NULL));

    // Build message after change status of issue
    $class = ($new_status == IssueEntity::STATUS_IGNORED) ? 'warning' : 'status';
    $message = t('Status of "@issue_name" issue was changed to "@status".', ['@issue_name' => $issue->__toString(), '@status' => $new_status]);
    $build_message = '<div class="messages messages--' . $class . '">' . $message . '</div>';
    $response->addCommand(new HtmlCommand($message_selector, $build_message, $settings = NULL));

    return $response;

  }

}
