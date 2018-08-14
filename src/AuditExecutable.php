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
  protected $test_id;

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
   * @param string $test_id
   *   The test plugin id.
   * @param array $configuration
   *   The plugin configuration,
   * @param \Drupal\adv_audit\Message\AuditMessageInterface $message
   *   (optional) The audit message service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   (optional) The event dispatcher.
   */
  public function __construct($test_id, array $configuration = [], AuditMessageInterface $message = NULL, EventDispatcherInterface $event_dispatcher = NULL) {
    $this->test_id = $test_id;
    $this->configuration = $configuration;
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
    // Set where we try to create plugin instance.
    $this->configuration['audit_execute'] = TRUE;
    try {
      try {
        // Init the audit plugin instance.
        $this->test = $this->container()->get('plugin.manager.adv_audit_check')->createInstance($this->test_id, $this->configuration);
        // Knock off test if the requirements haven't been met.
        $this->test->checkRequirements();
        // Run audit checkpoint perform.
        $return = $this->test->perform();
        // Check what plugin return correct response.
        if (!($return instanceof AuditReason)) {
          throw new AuditException('Response from ' . $this->test->id() . ' have a not correct type.');
        }
      }
      catch (RequirementsException $e) {
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
        throw new AuditSkipTestException('Audit checkpoint plugin not meet the requirements');
      }
      catch (AuditException $e) {
        throw new \Exception('Plugin logic problem: ' . $e->getPluginId(), 0 , $e);
      }
    }
    catch (AuditSkipTestException $e) {
      $return = new AuditReason($this->test->id(), AuditResultResponseInterface::RESULT_SKIP, 'Audit plugin was skip this audit point.');
      // Skip test and save log record.
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
    catch (\Exception $e) {
      // We should handle all exception what can occur in audit plugins.
      $this->handleException($e);
      // In any case we should store this result in Result response collections and mark it as Failed.
      $return = new AuditReason($this->test->id(), AuditResultResponseInterface::RESULT_FAIL, $e->getMessage());
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

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

}
