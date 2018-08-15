<?php

namespace Drupal\adv_audit;

use Drupal\adv_audit\Exception\AuditSkipTestException;
use Drupal\adv_audit\Exception\AuditException;
use Drupal\adv_audit\Message\AuditMessage;
use Drupal\adv_audit\Message\AuditMessageInterface;
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
    try {
      // Knock off test if the requirements haven't been met.
      $this->test->checkRequirements();
      // Run audit checkpoint perform.
      $result = $this->test->perform();
      // Check what plugin return correct response.
      if (!($result instanceof AuditReason)) {
        // Mark Result as Skipped.
        $msg = $this->t('AuditPlugin @id returned an invalid result. Expected instance of "AuditReason" but @type was found', [
          '@id' => $this->test->id(),
          '@type' => get_class($result) || gettype($result),
        ]);
        $this->message->display($msg, 'status');
        return new AuditReason($this->test->id(), AuditResultResponseInterface::RESULT_SKIP, $msg);
      }

      return $result;
    }
    catch (AuditSkipTestException $e) {
      $this->handleException($e);

      // Following message should be removed.
      // There is no sense to use separate message and method for requirements.
      $this->message->display(
        $this->t(
          'Audit checkpoint @id did not meet the requirements. @message @requirements',
          [
            '@id' => $this->test->id(),
            '@message' => $e->getMessage(),
            '@requirements' => $e->getRequirementsString(),
          ]
        ),
        'error'
      );

      // Skip test and save log record.
      $this->message->display($msg, 'status');
      $msg = $this->t('Audit Check @id was skipped due to missing requirements: @message', [
          '@id' => $this->test->id(),
          '@message' => $e->getMessage(),
        ]);
      return new AuditReason($this->test->id(), AuditResultResponseInterface::RESULT_SKIP, $msg);
    }
    catch (\Exception $e) {
      // We should handle all exceptions occurred during Audit execution.
      $this->handleException($e);
      // Mark Result as Skipped.
      return new AuditReason($this->test->id(), AuditResultResponseInterface::RESULT_SKIP, $e->getMessage());
    }
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
