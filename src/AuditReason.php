<?php

namespace Drupal\adv_audit;

use Drupal\adv_audit\Entity\IssueEntity;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class AuditReason.
 *
 * Use in audit test plugin for save perform result.
 *
 * @package Drupal\adv_audit
 */
class AuditReason {

  /**
   * The status code.
   *
   * @var int
   */
  protected $status;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $testId;

  /**
   * The main reason data.
   *
   * @var string
   */
  protected $reason;

  /**
   * An associative array of replacements.
   *
   * @var array
   */
  protected $arguments;

  /**
   * An array of reported issues.
   *
   * @var array
   */
  protected $issues;

  /**
   * AuditReason constructor.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param int $status
   *   The status of the perform test.
   * @param string $reason
   *   Reason why test is failed. (optional)
   * @param array|mixed $arguments
   *   (optional) An associative array of replacements to make after
   *   translation of status message.
   */
  public function __construct($plugin_id, $status, $reason = '', $arguments = []) {
    $this->status = $status;
    $this->testId = $plugin_id;
    $this->reason = $reason;
    $this->arguments = $arguments;
  }

  /**
   * Static method for create class instance.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param int $status
   *   The status of the perform test.
   * @param null|mixed $reason
   *   Reason why test is failed. (optional)
   * @param array|mixed $arguments
   *   (optional) An associative array of replacements to make after
   *   translation of status message.
   *
   * @return static
   */
  public static function create($plugin_id, $status, $reason = NULL, $arguments = []) {
    return new static($plugin_id, $status, $reason, $arguments);
  }

  /**
   * Return status of the test perform.
   *
   * @return mixed
   *   Return status code from AuditResultResponseInterface.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Get arguments value.
   *
   * @return array
   *   List of saved arguments.
   */
  public function getArguments() {
    return $this->arguments;
  }

  /**
   * Get list of available reasons from saved object.
   *
   * @return string
   *   Return string of reasons.
   */
  public function getReason() {
    return $this->reason;
  }

  /**
   * Check what current audit is pass all checks.
   *
   * @return bool
   *   Return TRUE if current audit is pass, otherwise FALSE.
   */
  public function isPass() {
    return $this->getStatus() == AuditResultResponseInterface::RESULT_PASS;
  }

  /**
   * Get current audit plugin id.
   *
   * @return string
   *   The plugin id value.
   */
  public function getPluginId() {
    return $this->testId;
  }

  /**
   * Get list of saved Issue entities.
   */
  public function getIssues(): array {
    if ($this->isPass()) {
      return [];
    }

//    if (!empty($this->issues)) {
//      return $this->issues;
//    }

    $details = $this->getArguments();
    if (empty($details['issues'])) {
      return [];
    }

    // Create/Load issues.
    $this->issues = [];
    foreach($details['issues'] as $issue_name => $details) {
      $issue_title = empty($details['@issue_title']) ? $issue_name : $details['@issue_title'];
      $issue = IssueEntity::create([
        'name' => $this->getPluginId() . '.' . $issue_name,
        'title' => $issue_title,
        'plugin' => $this->getPluginId(),
        'details' => serialize($details),
      ]);

      if (!$issue->isNew()) {
        // Update all details.
        $issue->setTitle($issue_title);
        $issue->setDetails(serialize($details));

        // Reopen the issue if it is fixed.
        if ($issue->isStatus(IssueEntity::STATUS_FIXED)) {
          $issue->setStatus(IssueEntity::STATUS_OPEN);
        }

        $issue->save();
      }

      $this->issues[] = $issue;
    }

    return $this->issues;
  }

  /**
   * Get list of Issue entities with Open status.
   */
  public function getOpenIssues() {
    $issues = $this->getIssues();
    $open_issues = [];
    foreach ($issues as $issue) {
      if ($issue->isOpen()) {
        $open_issues[] = $issue;
      }
    }

    return $open_issues;
  }

}
