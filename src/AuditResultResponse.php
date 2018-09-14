<?php

namespace Drupal\adv_audit;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\adv_audit\Plugin\AuditPluginInterface;
use JsonSerializable;
use Serializable;

/**
 *
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
   * @param \Drupal\adv_audit\Plugin\AuditPluginInterface $test
   *   Test plugin instance.
   * @param $status
   *   Execution status.
   *
   * @deprecated Use ::addReason method.
   *
   * @return void
   */
  public function addResultReport(AuditPluginInterface $test, $status = AuditResultResponseInterface::RESULT_INFO) {
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
   * Specify data which should be serialized to JSON.
   *
   * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
   *
   * @return mixed data which can be serialized by <b>json_encode</b>,
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
   * @return string the string representation of the object or null
   *
   * @since 5.1.0
   */
  public function serialize() {
    return serialize([
      'results' => $this->results,
      'overviewInfo' => $this->overviewInfo,
    ]);
  }

  /**
   * Constructs the object.
   *
   * @link http://php.net/manual/en/serializable.unserialize.php
   *
   * @param string $serialized
   *   <p>
   *   The string representation of the object.
   *   </p>.
   *
   * @return void
   *
   * @since 5.1.0
   */
  public function unserialize($serialized) {
    $data = unserialize($serialized);
    foreach ($data as $key => $value) {
      $this->{$key} = $value;
    }
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
