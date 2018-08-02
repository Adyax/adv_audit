<?php

namespace Drupal\adv_audit;


use Doctrine\Common\Collections\ArrayCollection;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use JsonSerializable;

class AuditResultResponse implements AuditResultResponseInterface, JsonSerializable {

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

  public function calculateScore() {
    // TODO: Implement calculateScore() method.
  }

  /**
   * Add result of the running test.
   *
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckInterface $test
   *   Test plugin instance.
   * @param $status
   *   Execution status.
   *
   * @return void
   */
  public function addResultReport(AdvAuditCheckInterface $test, $status = AuditResultResponseInterface::RESULT_INFO) {
    $this->results->add([
      'test_id' => $test->id(),
      'status' => $status,
      'category' => $test->getCategoryDefinitionPlugin()->id()
    ]);
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

}
