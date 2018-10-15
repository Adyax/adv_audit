<?php

namespace Drupal\adv_audit\Controller;

use Drupal\adv_audit\Entity\IssueEntity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Controller for changing Audit Issue status.
 */
class AuditIssueStatusController extends ControllerBase {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a AuditIssueStatusController object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time')
    );
  }

  /**
   * Changes issue status and creating new revision.
   *
   * @param integer $adv_audit_id
   *   The id of Audit entity.
   * @param \Drupal\adv_audit\Entity\IssueEntity $issue
   *   The current Audit Issue.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function issueChangeStatus($adv_audit_id, $issue) {
    $user_id = \Drupal::currentUser()->id();
    $statuses = IssueEntity::getStatuses();
    $current_status = $issue->getStatus();
    $available_status = array_diff($statuses, [$current_status => $current_status]);
    $new_status = array_values($available_status)[0];

    // Make this change a new revision
    $issue->setStatus($new_status);
    $issue->setNewRevision();
    $issue->setRevisionLogMessage($this->t('Status changed from @old_status to @new_status', [
      '@old_status' => $current_status,
      '@new_status' => $new_status,
    ]));
    $issue->setRevisionCreationTime($this->time->getRequestTime());
    $issue->setRevisionUserId($user_id);
    $issue->save();

    // Build message after change status of issue.
    $type = ($new_status == IssueEntity::STATUS_IGNORED) ? 'warning' : 'status';
    $message = t('%title issue status was changed to @status.', [
      '%title' => $issue->getTitle(),
      '@status' => $new_status,
    ]);
    $this->messenger()->addMessage($message, $type);

    return $this->redirect('entity.adv_audit.canonical', ['adv_audit' => $adv_audit_id]);
  }

}
