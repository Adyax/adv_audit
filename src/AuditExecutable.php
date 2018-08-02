<?php

namespace Drupal\adv_audit;

use Drupal\adv_audit\Exception\AuditSkipTestException;
use Drupal\adv_audit\Message\AuditMessage;
use Drupal\adv_audit\Message\AuditMessageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\Core\Utility\Error;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\adv_audit\Exception\RequirementsException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a migrate executable class.
 */
class AuditExecutable {
  use StringTranslationTrait;

  /**
   * List of test for performs.
   *
   * @var AdvAuditCheckInterface
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
   * Constructs a MigrateExecutable and verifies and sets the memory limit.
   *
   * @param \Doctrine\Common\Collections\ArrayCollection $test_list
   *   List of test for performs.
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
    } catch (RequirementsException $e) {
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
      return AuditResultResponseInterface::RESULT_FAIL;
    }

    $return = AuditResultResponseInterface::RESULT_INFO;

    try {
      $return = $this->test->perform();
    } catch (\Exception $e) {
      $return = AuditResultResponseInterface::RESULT_FAIL;
      $this->handleException($e);
    } catch (AuditSkipTestException $e) {
      if ($message = trim($e->getMessage())) {
        // Skip test and save log record.
        $return = AuditResultResponseInterface::RESULT_WARN;
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
    // this->getEventDispatcher()->dispatch(AdvAuditEvents::POST_PERFORM, new AuditEvent($this->test, $this->message));
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
    $message = $result['@message'] . ' (' . $result['%file'] . ':' . $result['%line'] . ')';
    $this->message->display($message, 'error');
  }

}
