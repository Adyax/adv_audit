<?php

namespace Drupal\adv_audit;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use JsonSerializable;
use Serializable;

class AuditResultResponse implements AuditResultResponseInterface, JsonSerializable, Serializable {

  /**
   * List of audit results.
   *
   * @var \Doctrine\Common\Collections\ArrayCollection
   */
  protected $results;

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
    $total_count = $this->results->count();
    foreach ($this->results->getIterator() as $check_result) {
      if ($check_result->getStatus() == AuditResultResponseInterface::RESULT_PASS) {
        $passed++;
      }
    }
    $score = ($passed * 100) / $total_count;
    return intval($score);
  }

  /**
   * Add result of the running test.
   *
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckInterface $test
   *   Test plugin instance.
   * @param $status
   *   Execution status.
   *
   * @deprecated Use ::addReason method.
   * @return void
   */
  public function addResultReport(AdvAuditCheckInterface $test, $status = AuditResultResponseInterface::RESULT_INFO) {
    $this->results->add(new AuditReason($test->id(), $status));
  }

  /**
   * Reason response from audit test plugin.
   *
   * @param \Drupal\adv_audit\AuditReason $reason
   *   The reason object from test plugin.
   */
  public function addReason(AuditReason $reason) {
    $this->results->add($reason);
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
   * Specify data which should be serialized to JSON
   *
   * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
   * @return mixed data which can be serialized by <b>json_encode</b>,
   * which is a value of any type other than a resource.
   * @since 5.4.0
   */
  public function jsonSerialize() {
    return $this->results->toArray();
  }

  /**
   * String representation of object
   *
   * @link http://php.net/manual/en/serializable.serialize.php
   * @return string the string representation of the object or null
   * @since 5.1.0
   */
  public function serialize() {
    return serialize($this->results);
  }

  /**
   * Constructs the object
   *
   * @link http://php.net/manual/en/serializable.unserialize.php
   *
   * @param string $serialized <p>
   * The string representation of the object.
   * </p>
   *
   * @return void
   * @since 5.1.0
   */
  public function unserialize($serialized) {
    $this->results = unserialize($serialized);
  }

}
