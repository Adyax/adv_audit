<?php

namespace Drupal\Tests\adv_audit\Unit;

use Drupal\adv_audit\AuditExecutable;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the AuditExecutable class.
 *
 * @coversDefaultClass \Drupal\adv_audit\AuditExecutable
 * @group adv_audit
 */
class AuditExecutableTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $plugin = $this->createMock(AdvAuditCheckBase::class);
    $plugin->expects($this->any())
      ->method('checkRequirements')
      ->willReturn(TRUE);
    $plugin->expects($this->any())
      ->method('perform')
      ->willReturnCallback(function () {
        return new AuditReason('test', AuditResultResponseInterface::RESULT_PASS);
      });
    $checkManager = $this->getMockBuilder('Drupal\adv_audit\Plugin\AdvAuditCheckManager')
      ->disableOriginalConstructor()
      ->getMock();
    $checkManager->expects($this->any())
      ->method('createInstance')
      ->willReturn($plugin);
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('plugin.manager.adv_audit_check', $checkManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the performTest method with test plugin.
   *
   * @covers ::performTest
   */
  public function testPerformTest() {
    $executable = new AuditExecutable('test');
    $response = $executable->performTest();

    $this->assertInstanceOf('Drupal\adv_audit\AuditReason', $response);
  }

  /**
   * Tests the run method.
   *
   * @covers ::run
   */
  public function testRun() {
    list($audit_reason, $audit_messages) = AuditExecutable::run('test');

    $this->assertInstanceOf('Drupal\adv_audit\AuditReason', $audit_reason);
    $this->assertInternalType('array', $audit_messages);
  }

}
