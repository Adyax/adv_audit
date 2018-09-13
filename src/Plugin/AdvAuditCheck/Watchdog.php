<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Provide watchdog analyze.
 *
 * @AdvAuditCheck(
 *  id = "watchog",
 *  label = @Translation("Analyze Watchdog Logs."),
 *  category = "server_configuration",
 *  severity = "normal",
 *  enabled = true,
 *  requirements = {},
 * )
 */
class Watchdog extends AdvAuditCheckBase {

  /**
   * The actual procedure of carrying out the check.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function perform() {
    $issues = [];

    if ($this->moduleHandler->moduleExists('dblog')) {
      $total = $this->getRowCount();
      if ($total) {
        $issues['total'] = $total;
        if ($not_found = $this->getNotFoundCount()) {
          $issues['page_not_found'] = $not_found;
        }
        $issues['age'] = $this->getAge();
        if ($php = $this->getCountPhpErrors()) {
          $issues['php'] = $php;
        }
      }

    }
    if (!$this->moduleHandler->moduleExists('syslog')) {
      $issues['syslog'] = [
        '@issue_title' => "Module Syslog is not enabled",
      ];
    }

    if (!empty($issues)) {
      $this->fail(NULL, ['issues' => $issues]);
    }
    return $this->success();
  }

  /**
   * Calculate Page Not Found messages.
   */
  public function getNotFoundCount() {
    $count = db_select('watchdog', 'w')
      ->fields('w')
      ->condition('w.type', 'page not found')
      ->countQuery()
      ->execute()
      ->fetchField();
    $count_rows = db_select('watchdog', 'w')
      ->fields('w')
      ->countQuery()
      ->execute()
      ->fetchField();
    $percent = 0;
    if ($count) {
      $percent = round($count / $count_rows * 100);
    }
    if ($percent >= 10) {
      return [
        '@issue_title' => "@count_404 pages not found (@percent_404%).",
        '@count_404' => $count,
        '@percent_404' => $percent,
      ];
    }

    return FALSE;
  }

  /**
   * Calculate total amount of messages.
   */
  public function getRowCount() {
    $count_rows = db_select('watchdog', 'w')
      ->fields('w')
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($count_rows) {
      return [
        '@issue_title' => "There are @count_entries log entries.",
        '@count_entries' => $count_rows,
      ];
    }
    return FALSE;
  }

  /**
   * Get age of watchdog messages.
   */
  public function getAge() {
    // Age of oldest entry.
    $old = db_select('watchdog', 'w')
      ->fields('w', ['timestamp'])
      ->orderBy('wid', 'ASC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    // Age of newest entry.
    $new = db_select('watchdog', 'w')
      ->fields('w', ['timestamp'])
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    // If two different days...
    if (date('Y-m-d', $old) != date('Y-m-d', $new)) {
      return [
        '@issue_title' => "From @from to @to (@days days)",
        '@from' => date('r', $old),
        '@to' => date('r', $new),
        '@days' => round(($new - $old) / 86400, 2),
      ];
    }
    // Same day; don't calculate number of days.
    return [
      '@issue_title' => "From @from to @to",
      '@from' => date('r', $old),
      '@to' => date('r', $new),
    ];
  }

  /**
   * Calculate PHP messages.
   */
  public function getCountPhpErrors() {
    $php_messages = [];

    $message_types = _dblog_get_message_types();
    if (!in_array('php', $message_types)) {
      return FALSE;
    }

    $php_total_count = db_select('watchdog', 'w')
      ->fields('w')
      ->condition('w.type', 'php')
      ->countQuery()
      ->execute()
      ->fetchField();
    $count_rows = db_select('watchdog', 'w')
      ->fields('w')
      ->countQuery()
      ->execute()
      ->fetchField();

    $severity_types = RfcLogLevel::getLevels();
    foreach ($severity_types as $key => $label) {
      $count_messages = db_select('watchdog', 'w')
        ->fields('w')
        ->condition('w.severity', $key)
        ->countQuery()
        ->execute()
        ->fetchField();
      if ($count_messages) {
        $php_messages[$key] = $count_messages;
      }
    }

    $php_percent = round(($php_total_count / $count_rows) * 100, 2);
    if ($php_percent >= 10) {
      $issue = [];
      foreach ($php_messages as $key => $count) {
        $issue[] = $severity_types[$key] . ':' . $count;
      }
      $text = implode(', ', $issue);
      $text .= ' - total ' . $php_percent . '%';
      return [
        '@issue_title' => '@message',
        '@message' => $text,
      ];

    }
    return FALSE;
  }

}
