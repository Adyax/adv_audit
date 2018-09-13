<?php

namespace Drupal\Tests\adv_audit\Unit;

use Drupal\adv_audit\AuditExecutable;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AuditPluginsBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

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
    $channel = $this->createMock(LoggerInterface::class);
    $channel->expects($this->any())
      ->method('error')
      ->willReturn(TRUE);
    $logger = $this->createMock(LoggerChannelFactory::class);
    $logger->expects($this->any())
      ->method('get')
      ->willReturn($channel);
    $plugin = $this->createMock(AdvAuditCheckBase::class);
    $plugin->expects($this->any())
      ->method('checkRequirements')
      ->willReturn(TRUE);
    $plugin->expects($this->any())
      ->method('perform')
      ->willReturnCallback(function () {
        return new AuditReason('test', AuditResultResponseInterface::RESULT_PASS);
      });

    $moc_plugin = $this->createMock(AdvAuditCheckBase::class);
    $moc_plugin->expects($this->any())
      ->method('perform')
      ->willReturn('');
    $checkManager = $this->getMockBuilder('Drupal\adv_audit\Plugin\AuditPluginsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $checkManager->expects($this->any())
      ->method('createInstance')
      ->willReturnCallback(function ($test_id) use ($plugin, $moc_plugin) {
        if ('test' == $test_id) {
          return $plugin;
        }
        return $moc_plugin;
      });
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('plugin.manager.adv_audit_check', $checkManager);
    $container->set('logger.factory', $logger);
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
    $this->assertEquals(AuditResultResponseInterface::RESULT_PASS, $audit_reason->getStatus());

    list($audit_reason, $audit_messages) = AuditExecutable::run('unexisted_plugin');

    $this->assertInstanceOf('Drupal\adv_audit\AuditReason', $audit_reason);
    $this->assertInternalType('array', $audit_messages);
    $this->assertEquals(AuditResultResponseInterface::RESULT_SKIP, $audit_reason->getStatus());
  }

}
