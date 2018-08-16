<?php

namespace Drupal\adv_audit;

use Drupal\adv_audit\Exception\AuditSkipTestException;
use Drupal\adv_audit\Message\AuditMessageCapture;
use Drupal\adv_audit\Message\AuditMessageInterface;
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
   * Defines a value for designating the usage context.
   *
   * Use this flag when you try to create instance plugin for determining
   * the action to run audit scenarios.
   *
   * @see \Drupal\adv_audit\AuditExecutable::performTest().
   */
  const AUDIT_EXECUTE_RUN = 'audit_execute';

  /**
   * The test instance to perform.
   *
   * @var \Drupal\adv_audit\Plugin\AdvAuditCheckInterface
   */
  protected $test;

  /**
   * The test ID.
   *
   * @var string
   */
  protected $testId;

  /**
   * The configuration for initialize instance.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Audit messages.
   *
   * @var \Drupal\adv_audit\Message\AuditMessageInterface
   */
  public $message;

  /**
   * Constructs a AuditExecutable and verifies.
   *
   * @param string $test_id
   *   The test plugin id.
   * @param array $configuration
   *   The plugin configuration.
   * @param \Drupal\adv_audit\Message\AuditMessageInterface $message
   *   (optional) The audit message service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   (optional) The event dispatcher.
   */
  public function __construct($test_id, array $configuration = [], AuditMessageInterface $message = NULL, EventDispatcherInterface $event_dispatcher = NULL) {
    $this->testId = $test_id;
    $this->configuration = $configuration;
    $this->message = $message ?: new AuditMessageCapture();
    $this->eventDispatcher = $event_dispatcher;
  }

  public static function run($test_id) {
    $executable = new AuditExecutable($test_id);

    return [$executable->performTest(), $executable->message->getMessages()];
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
      // Set where we try to create plugin instance.
      $this->configuration[self::AUDIT_EXECUTE_RUN] = TRUE;

      // Init the audit plugin instance.
      $this->test = \Drupal::service('plugin.manager.adv_audit_check')->createInstance($this->testId, $this->configuration);
      // Knock off test if the requirements haven't been met.
      $this->test->checkRequirements();
      // Run audit checkpoint perform.
      $result = $this->test->perform();
      // Check what plugin return correct response.
      if (!($result instanceof AuditReason)) {
        $result_type = get_class($result);
        if (empty($result_type)) {
          $result_type = gettype($result);
        }
        // Mark Result as Skipped.
        $msg = $this->t('AuditPlugin @id returned an invalid result. Expected instance of "AuditReason" but @type was found', [
          '@id' => $this->testId,
          '@type' => $result_type,
        ]);
        $this->message->display($msg, 'status');

        return new AuditReason($this->testId, AuditResultResponseInterface::RESULT_SKIP, $msg);
      }

      return $result;
    }
    catch (RequirementsException $e) {
      $msg = $this->t('Audit `@id` did not meet the requirements. @message @requirements', [
        '@id' => $this->testId,
        '@message' => $e->getMessage(),
        '@requirements' => $e->getRequirementsString(),
      ]);

      return $this->handleExecutionException($e, $msg);
    }
    catch (AuditSkipTestException $e) {
      $msg = $this->t('Audit `@id` was skipped due to missing requirements: @message', [
        '@id' => $this->testId,
        '@message' => $e->getMessage(),
      ]);

      return $this->handleExecutionException($e, $msg);
    }
    catch (\Exception $e) {
      // We should handle all exceptions occurred during Audit execution.
      return $this->handleExecutionException($e);
    }
  }

  /**
   * Takes an Exception object and both saves and displays it.
   *
   * Pulls in additional information on the location triggering the exception.
   *
   * @param \Exception $exception
   *   Object representing the exception.
   * @param string $msg
   *   The error message.
   * @param string $msg_type
   *   The type of message.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   The AuditReason object.
   */
  protected function handleExecutionException(\Exception $exception, $msg = '', $msg_type = 'status') {
    $result = Error::decodeException($exception);

    $handle_message = $this->t('Audit Check @id was skipped due to exception $msg in @file, line:@line)', [
      '@id' => $this->testId,
      '@message' => $exception->getMessage(),
      '@file' => $result['%file'],
      '@line' => $result['%line'],
    ]);

    \Drupal::logger('adv_audit_batch')->error($handle_message);

    if (empty($msg)) {
      $msg = $handle_message;
    }
    // Display Status Message.
    $this->message->display($msg, $msg_type);

    // Mark Result as Skipped.
    return new AuditReason($this->testId, AuditResultResponseInterface::RESULT_SKIP, $msg);
  }

}
