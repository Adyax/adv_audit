<?php

namespace Drupal\adv_audit;

use Drupal\adv_audit\Exception\AuditSkipTestException;
use Drupal\adv_audit\Exception\AuditTestException;
use Drupal\adv_audit\Message\AuditMessage;
use Drupal\adv_audit\Message\AuditMessageInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\Core\Utility\Error;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\adv_audit\Exception\RequirementsException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a audit executable class.
 */
class AuditExecutable {
  use StringTranslationTrait;

  /**
   * List of test for performs.
   *
   * @var \Drupal\adv_audit\Plugin\AdvAuditCheckInterface
   */
  protected $test;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Migration audit service.
   *
   * @todo Make this protected.
   *
   * @var \Drupal\adv_audit\Message\AuditMessageInterface
   */
  public $message;

  /**
   * Constructs a AuditExecutable and verifies.
   *
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckInterface $test
   *   The test plugin instance.
   * @param \Drupal\adv_audit\Message\AuditMessageInterface $message
   *   (optional) The audit message service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   (optional) The event dispatcher.
   */
  public function __construct(AdvAuditCheckInterface $test, AuditMessageInterface $message = NULL, EventDispatcherInterface $event_dispatcher = NULL) {
    $this->test = $test;
    $this->message = $message ?: new AuditMessage();
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Gets the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   Return Event dispatcher service instance.
   */
  protected function getEventDispatcher() {
    if (!$this->eventDispatcher) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function performTest() {
    // Send event before perform the test.
    // $this->getEventDispatcher()->dispatch(AdvAuditEvents::PRE_PERFORM, new AuditEvent($this->test, $this->message));

    // Knock off test if the requirements haven't been met.
    try {
      $this->test->checkRequirements();
    }
    catch (RequirementsException $e) {
      $this->message->display(
        $this->t(
          'Test @id did not meet the requirements. @message @requirements',
          [
            '@id' => $this->test->id(),
            '@message' => $e->getMessage(),
            '@requirements' => $e->getRequirementsString(),
          ]
        ),
        'error'
      );
      return AuditResultResponseInterface::RESULT_SKIP;
    }

    try {
      $return = $this->test->perform();
    } catch (AuditSkipTestException $e) {
      if ($e->getMessage()) {
        // Skip test and save log record.
        $return = AuditReason::create($this->test->id(), AuditResultResponseInterface::RESULT_SKIP, AuditMessagesStorageInterface::MSG_TYPE_FAIL);
        $this->message->display(
          $this->t(
            'Test @id was skipped. @message',
            [
              '@id' => $this->test->id(),
              '@message' => $e->getMessage(),
            ]
          ),
          'status');
      }
    }
    catch (AuditTestException $e) {
      $return = AuditReason::create($this->test->id(), AuditResultResponseInterface::RESULT_FAIL, AuditMessagesStorageInterface::MSG_TYPE_FAIL);
      $this->handleException($e);
    }

    return $return;
  }

  /**
   * Takes an Exception object and both saves and displays it.
   *
   * Pulls in additional information on the location triggering the exception.
   *
   * @param \Exception $exception
   *   Object representing the exception.
   */
  protected function handleException(\Exception $exception) {
    $result = Error::decodeException($exception);
    $handle_message = $result['@message'] . ' (' . $result['%file'] . ':' . $result['%line'] . ')';
    $this->message->display($handle_message, 'error');
  }

}
