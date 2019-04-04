<?php

namespace Drupal\adv_audit\Batch;

use Drupal\adv_audit\AuditExecutable;
use Drupal\adv_audit\AuditResultResponse;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Entity\AuditEntity;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use InvalidArgumentException;

/**
 * Runs a single test batch.
 */
class AuditRunBatch {

  /**
   * Maximum number of previous messages to display.
   */
  const MESSAGE_LENGTH = 20;

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
   * @param object $context
   *   The batch context.
   */
  public static function run(array $initial_ids, array $config, &$context) {
    if (!isset($context['sandbox']['test_ids'])) {
      $context['sandbox']['max'] = count($initial_ids);
      $context['sandbox']['current'] = 1;
      // Total number processed for this audit.
      $context['sandbox']['num_processed'] = 0;
      // test_ids will be the list of IDs remaining to run.
      $context['sandbox']['test_ids'] = $initial_ids;
      $context['results']['messages'] = [];
      // @var \Drupal\adv_audit\AuditResultResponseInterface
      $context['results']['result_response'] = new AuditResultResponse();
      $context['results']['@skip_count'] = 0;
      $context['results']['@fail_count'] = 0;
      $context['results']['@success_count'] = 0;
    }

    /** @var \Drupal\adv_audit\AuditResultResponse $result_response */
    $result_response = &$context['results']['result_response'];

    // Take current Audit off the list.
    $test_id = array_shift($context['sandbox']['test_ids']);
    $context['sandbox']['current']++;

    if ($test_id) {
      list($audit_reason, $audit_messages) = AuditExecutable::run($test_id);

      // Save audit checkpoint result.
      $result_response->addReason($audit_reason);
      $audit_result_status = $audit_reason->getStatus();
      $message = new TranslatableMarkup('Audit `@test` is @status', [
        '@test' => $test_id,
        '@status' => $audit_result_status,
      ]);
      $context['results']['messages'][] = (string) $message;
      $context['sandbox']['num_processed']++;

      switch ($audit_result_status) {
        case AuditResultResponseInterface::RESULT_PASS:
          // Store the number processed in the sandbox.
          $context['results']['@success_count']++;
          break;

        case AuditResultResponseInterface::RESULT_FAIL:
          $context['results']['@fail_count']++;
          break;

        case AuditResultResponseInterface::RESULT_SKIP:
          $context['results']['@skip_count']++;
          break;

        default:
          break;
      }

      // Add and log any captured messages.
      foreach ($audit_messages as $message) {
        $context['results']['messages'][] = (string) $message;
      }
    }

    static::displayMessages($context);
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
  public static function finished($success, array $results, array $operations, $elapsed) {
    drupal_set_message(t('Audit process has finished: @success_count succeed, @fail_count failed, @skip_count skipped.', $results));
    // If all are OK then try to save audit report result to the entity.
    $audit_result_response = $results['result_response'];
    // Check what we have valid result object.
    if (!($audit_result_response instanceof AuditResultResponse)) {
      drupal_set_message(t("Can't save audit result to the entity. Expected AuditResultResponse."), 'error');
      return;
    }

    try {
      // Create Audit report.
      $entity = AuditEntity::create([
        'name' => AuditEntity::generateEntityName(),
      ]);

      // Include  Global Info if it enabled.
      $global_info_status = \Drupal::config('adv_audit.settings')
        ->get('categories');
      if ($global_info_status['global_info']['status'] === 1) {
        // Get global info.
        $advGlobalData = \Drupal::service('adv_audit.global_info');
        $resultsGlobal = $advGlobalData->index();

        // Set global info.
        $audit_result_response->setOverviewInfo($resultsGlobal);
      }
      $entity->setIssues($audit_result_response);
      $entity->setAuditResults($audit_result_response);
      $entity->save();

      if (PHP_SAPI === 'cli') {
        $test = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
        // Do stuff.
        drush_print(dt('Batch is completed. View audit @url', ['@url' => $test]));
      }
      else {
        drupal_set_message(t('The Audit result was saved. View audit report: %link', ['%link' => $entity->link('Report')]));
        // Display all Audit messages.
        foreach ($results['messages'] as $audit_msg) {
          drupal_set_message($audit_msg);
        }
      }

    } catch (EntityStorageException $e) {
      drupal_set_message(t("Can't save audit result to the entity due to exception with msg: @message", ['@message' => $e->getMessage()]), 'error');
    } catch (InvalidArgumentException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Display Batch messages during batch progress.
   *
   * @param mixed $context
   *   Context of the batch.
   */
  protected static function displayMessages(&$context) {
    // Only display the last MESSAGE_LENGTH messages, in reverse order.
    $message_count = count($context['results']['messages']);
    $context['message'] = '';
    for ($index = max(0, $message_count - self::MESSAGE_LENGTH); $index < $message_count; $index++) {
      $context['message'] = $context['results']['messages'][$index] . "<br />\n" . $context['message'];
    }

    // Indicate if there are earlier messages not displayed.
    if ($message_count > self::MESSAGE_LENGTH) {
      $context['message'] .= '&hellip;';
    }

    // At the top of the list, display the next one (which will be the one
    // that is running while this message is visible).
    if (!empty($context['sandbox']['test_ids'])) {
      $test_id = reset($context['sandbox']['test_ids']);

      $context['message'] = (string) new TranslatableMarkup('Currently perform @test (@current of @max audits)', [
          '@test' => $test_id,
          '@current' => $context['sandbox']['current'],
          '@max' => $context['sandbox']['max'],
        ]) . "<br />\n" . $context['message'];
    }
  }

}
