<?php

namespace Drupal\Tests\adv_audit\Kernel;

use Drupal\adv_audit\Batch\AuditRunBatch;
use Drupal\adv_audit\Entity\AdvAuditEntity;
use Drupal\adv_audit\AuditResultResponse;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the AdvAuditEntity access control.
 *
 * @group adv_audit
 */
class AuditRunBatchTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['adv_audit', 'system', 'user', 'options'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('adv_audit');
  }

  /**
   * Tests that on run batch AuditResultResponse will be created.
   */
  public function testRunBatch() {
    $context = [];
    AuditRunBatch::run(['php_max_execution_time'], [], $context);
    // If all are OK then try to save audit report result to the entity.
    $audit_result_response = $context['results']['result_response'];
    // Check what we have valid result object.
    $this->assertInstanceOf(AuditResultResponse::class, $audit_result_response);
  }

  /**
   * Tests that on finished batch AdvAuditEntity will be created.
   */
  public function testFinishedBatch() {
    $entities = AdvAuditEntity::loadMultiple();
    // No entities for test yet.
    $this->assertEmpty($entities);
    $context = [];
    AuditRunBatch::run(['php_max_execution_time'], [], $context);
    AuditRunBatch::finished(TRUE, $context['results'], [], '');
    $entities = AdvAuditEntity::loadMultiple();
    // Must be one AdvAuditEntity created.
    $this->assertCount(1, $entities);
  }

}
