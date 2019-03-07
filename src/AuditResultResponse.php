<?php

namespace Drupal\adv_audit;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\adv_audit\Plugin\AuditPluginInterface;
use JsonSerializable;
use Serializable;

/**
 * Audit Result Response Class.
 */
class AuditResultResponse implements AuditResultResponseInterface, JsonSerializable, Serializable {

  /**
   * List of audit results.
   *
   * @var \Doctrine\Common\Collections\ArrayCollection
   */
  protected $results;

  /**
   * Store global information.
   *
   * @var mixed
   */
  protected $overviewInfo;

  /**
   * AuditResultResponse constructor.
   */
  public function __construct() {
    $this->results = new ArrayCollection();
  }

  /**
   * Calculate main scope of paddes audit.
   *
   * @return int
   *   Return scope value.
   */
  public function calculateScore() {
    $passed = 0;

    // Prevent division by zero.
    $total_count = $this->results->count() ? $this->results->count() : 1;
    foreach ($this->results->getIterator() as $audit_result) {
      if ($audit_result->getStatus() == AuditResultResponseInterface::RESULT_SKIP) {
        // Skip.
        $total_count--;
        continue;
      }

      if ($audit_result->getStatus() == AuditResultResponseInterface::RESULT_PASS) {
        $passed++;
      }

      if ($audit_result->getStatus() == AuditResultResponseInterface::RESULT_FAIL) {
        // Check active issues.
        $open_issues = $audit_result->getOpenIssues();
        if (empty($open_issues)) {
          $passed++;
        }
      }
    }

    $score = ($passed * 100) / $total_count;
    return intval($score);
  }

  /**
   * Add result of the running test.
   *
   * @param \Drupal\adv_audit\Plugin\AuditPluginInterface $test
   *   Test plugin instance.
   * @param string $status
   *   Execution status.
   *
   * @deprecated Use ::addReason method.
   */
  public function addResultReport(AuditPluginInterface $test, $status = AuditResultResponseInterface::RESULT_SKIP) {
    $reason = new AuditReason($test->id(), $status);
    $this->results->add($reason->toArray());
  }

  /**
   * Reason response from audit test plugin.
   *
   * @param \Drupal\adv_audit\AuditReason $reason
   *   The reason object from test plugin.
   *
   * @param bool $to_array
   *   The reason object from test plugin.
   */
  public function addReason(AuditReason $reason, $to_array = true) {
    if ($to_array) {
      $this->results->add($reason->toArray());
    }
    else {
      $this->results->add($reason);
    }
  }

  /**
   * Get stored audit results list.
   *
   * @return array
   *   The audit result list.
   */
  public function getAuditResults() {
    return $this->results->getValues();
  }

  /**
   * Specify data which should be serialized to JSON.
   *
   * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
   *
   * @return mixed
   *   Data which can be serialized by <b>json_encode</b>,
   *   which is a value of any type other than a resource.
   *
   * @since 5.4.0
   */
  public function jsonSerialize() {
    return $this->results->toArray();
  }

  /**
   * String representation of object.
   *
   * @link http://php.net/manual/en/serializable.serialize.php
   *
   * @return string
   *   The string representation of the object or null.
   *
   * @since 5.1.0
   */
  public function serialize() {
    return serialize([
      'results' => $this->results->toArray(),
      'overviewInfo' => $this->overviewInfo,
    ]);
  }

  /**
   * Constructs the object.
   *
   * @link http://php.net/manual/en/serializable.unserialize.php
   *
   * @param string $serialized
   *   The string representation of the object.
   *
   * @since 5.1.0
   */
  public function unserialize($serialized) {
    if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
      $data = unserialize($serialized, ['allowed_classes' => FALSE]);
    }
    else {
      $data = unserialize($serialized);
    }

    $this->results =  new ArrayCollection($data['results']);
    $this->overviewInfo = $data['overviewInfo'];
  }

  /**
   * Get overview information data.
   *
   * @return mixed
   *   Return array.
   */
  public function getOverviewInfo() {
    return $this->overviewInfo;
  }

  /**
   * Set overview information data.
   *
   * @param mixed $overviewInfo
   *   Information for save.
   */
  public function setOverviewInfo($overviewInfo) {
    $this->overviewInfo = $overviewInfo;
  }

}
