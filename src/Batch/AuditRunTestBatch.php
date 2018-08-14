<?php

namespace Drupal\adv_audit\Batch;

use Drupal\adv_audit\AuditExecutable;
use Drupal\adv_audit\AuditResultResponse;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Entity\AdvAuditEntity;
use Drupal\adv_audit\Message\AuditMessageCapture;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use InvalidArgumentException;

/**
 * Runs a single test batch.
 */
class AuditRunTestBatch {

  /**
   * Maximum number of previous messages to display.
   */
  const MESSAGE_LENGTH = 20;

  /**
   * The processed items for one batch of a given audit.
   *
   * @var int
   */
  protected static $numProcessed = 0;

  /**
   * AuditMessage instance to capture messages during the perform test process.
   *
   * @var \Drupal\adv_audit\Message\AuditMessageCapture
   */
  protected static $messages;

  /**
   * Runs a single test batch.
   *
   * @param int[] $initial_ids
   *   The full set of Audit test IDs to perform.
   * @param array $config
   *   An array of additional configuration from the form.
   * @param array $context
   *   The batch context.
   */
  public static function run($initial_ids, $config, &$context) {
    if (!isset($context['sandbox']['test_ids'])) {
      $context['sandbox']['max'] = count($initial_ids);
      $context['sandbox']['current'] = 1;
      // Total number processed for this audit.
      $context['sandbox']['num_processed'] = 0;
      // test_ids will be the list of IDs remaining to run.
      $context['sandbox']['test_ids'] = $initial_ids;
      $context['sandbox']['messages'] = [];
      /** @var \Drupal\adv_audit\AuditResultResponseInterface */
      $context['results']['result_response'] = new AuditResultResponse();
      $context['results']['failures'] = 0;
      $context['results']['successes'] = 0;
      $context['results']['report_entity'] = isset($config['report_entity']) ? $config['report_entity'] : NULL;
    }

    /** @var AuditResultResponse $result_response */
    $result_response = &$context['results']['result_response'];

    // Number processed in this batch.
    static::$numProcessed = 0;

    $test_id = reset($context['sandbox']['test_ids']);
    $configuration = [];

    /** @var \Drupal\adv_audit\Plugin\AdvAuditCheckBase $test */
    $test = \Drupal::service('plugin.manager.adv_audit_check')->createInstance($test_id, $configuration);

    if ($test instanceof AdvAuditCheckInterface) {
      static::$messages = new AuditMessageCapture();
      $executable = new AuditExecutable($test, static::$messages);

      $test_name = $test->label() ? $test->label() : $test_id;

      $test_reason = $executable->performTest();

      // Save audit checkpoint result.
      $result_response->addReason($test_reason);

      switch ($test_reason->getStatus()) {
        case AuditResultResponseInterface::RESULT_PASS:
          // Store the number processed in the sandbox.
          $context['sandbox']['num_processed'] += static::$numProcessed;
          $message = new TranslatableMarkup('Audit @test is PASSED', ['@test' => $test_name]);
          $context['sandbox']['messages'][] = (string) $message;
          \Drupal::logger('adv_audit_batch')->notice($message);
          $context['sandbox']['num_processed'] = 0;
          $context['results']['successes']++;
          break;

        case AuditResultResponseInterface::RESULT_FAIL:
          $context['sandbox']['messages'][] = (string) new TranslatableMarkup('Audit @test is FAILED', ['@test' => $test_name]);
          $context['results']['failures']++;
          \Drupal::logger('adv_audit_batch')->error('Audit @test is failed', ['@test' => $test_name]);
          break;

        case AuditResultResponseInterface::RESULT_SKIP:
          $context['sandbox']['messages'][] = (string) new TranslatableMarkup('Audit test @test skipped due to unfulfilled dependencies', ['@test' => $test_name]);
          \Drupal::logger('adv_audit_batch')->error('Audit on @test skipped due to unfulfilled dependencies', ['@test' => $test_name]);
          break;

        default:
          break;
      }

      // Unless we're continuing on with this test, take it off the list.
      array_shift($context['sandbox']['test_ids']);
      $context['sandbox']['current']++;

      // Add and log any captured messages.
      foreach (static::$messages->getMessages() as $message) {
        $context['sandbox']['messages'][] = (string) $message;
        \Drupal::logger('adv_audit_batch')->error($message);
      }

      // Only display the last MESSAGE_LENGTH messages, in reverse order.
      $message_count = count($context['sandbox']['messages']);
      $context['message'] = '';
      for ($index = max(0, $message_count - self::MESSAGE_LENGTH); $index < $message_count; $index++) {
        $context['message'] = $context['sandbox']['messages'][$index] . "<br />\n" . $context['message'];
      }
      if ($message_count > self::MESSAGE_LENGTH) {
        // Indicate there are earlier messages not displayed.
        $context['message'] .= '&hellip;';
      }
      // At the top of the list, display the next one (which will be the one
      // that is running while this message is visible).
      if (!empty($context['sandbox']['test_ids'])) {
        $test_id = reset($context['sandbox']['test_ids']);
        $test = \Drupal::service('plugin.manager.adv_audit_check')->createInstance($test_id);
        $test_name = $test->label() ? $test->label() : $test_id;
        $context['message'] = (string) new TranslatableMarkup('Currently perform @test (@current of @max audits)', [
          '@test' => $test_name,
          '@current' => $context['sandbox']['current'],
          '@max' => $context['sandbox']['max'],
        ]) . "<br />\n" . $context['message'];
      }
    }
    else {
      array_shift($context['sandbox']['test_ids']);
      $context['sandbox']['current']++;
    }

    $context['finished'] = 1 - count($context['sandbox']['test_ids']) / $context['sandbox']['max'];
  }

  /**
   * Callback executed when the Audit test perform batch process completes.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   An array of methods run in the batch.
   * @param string $elapsed
   *   The time to run the batch.
   */
  public static function finished($success, $results, $operations, $elapsed) {
    $successes = $results['successes'];
    $failures = $results['failures'];

    // If we had any successes log that for the user.
    if ($successes > 0) {
      drupal_set_message(\Drupal::translation()
        ->formatPlural($successes, 'Process 1 audit successfully', 'Processed @count audits successfully'));
    }
    // If we had failures, log them and show the test failed.
    if ($failures > 0) {
      drupal_set_message(\Drupal::translation()
        ->formatPlural($failures, '1 checkpoint failed', '@count checkpoints failed'));
      drupal_set_message(t('Audit process not fully completed'), 'error');
    }
    else {
      // Everything went off without a hitch. We may not have had successes
      // but we didn't have failures so this is fine.
      drupal_set_message(t('Congratulations, you process all audit checkpoints on your Drupal site!'));
    }

    if (!$success) {
      drupal_set_message('The batch process is not fully completed.', 'error');
      return;
    }

    // If all are OK then try to save audit report result to the entity.
    $audit_result_response = $results['result_response'];
    // In case when we already have result entity.
    // Occurred when we save new revision.
    $entity = $results['report_entity'];

    // Check what we have valid result object.
    if (!($audit_result_response instanceof AuditResultResponse)) {
      drupal_set_message('Can\'t save audit result to the entity. Have problem with result object.', 'error');
      return;
    }
    // Check the destination entity.
    if (is_null($entity) || !($entity instanceof AdvAuditEntity)) {
      $entity = AdvAuditEntity::create([
        'name' => AdvAuditEntity::generateEntityName(),
      ]);
    }

    try {
      $args = [];
      $entity->set('audit_results', serialize($audit_result_response));
      if ($entity->isNew()) {
        $args['@is_new'] = 'new';
      }
      else {
        $args['@is_new'] = '';
      }
      $entity->save();
      $args['%link'] = $entity->link('link');
      drupal_set_message(t('The @is_new entity was saved. View audit report by this %link', $args));
    }
    catch (EntityStorageException $e) {
      drupal_set_message('Can\'t save audit result to the entity. Save operations is failed.', 'error');
    }
    catch (InvalidArgumentException $e) {
      drupal_set_message('The specified audit_results field does not exist.', 'error');
    }
  }

  /**
   * Save audit response result to the entity.
   *
   * @param \Drupal\adv_audit\AuditResultResponse $auditResultResponse
   *   The response result object.
   * @param mixed $entity
   *   The Entity object for save.
   */
  protected function saveResult(AuditResultResponse $auditResultResponse, $entity = NULL ) {
    if (is_null($entity) || !($entity instanceof AdvAuditEntity)) {
      $entity = AdvAuditEntity::create([
        'name' => AdvAuditEntity::generateEntityName(),
      ]);
    }
    $entity->set('audit_results', serialize($auditResultResponse));
    $entity->save();
  }

}
