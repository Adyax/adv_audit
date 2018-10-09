<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;

/**
 * Provide watchdog analyze.
 *
 * @AuditPlugin(
 *  id = "watchdog",
 *  label = @Translation("Analyze Watchdog Logs."),
 *  category = "server_configuration",
 *  requirements = {},
 * )
 */
class ServerWatchdog extends AuditBasePlugin implements ContainerFactoryPluginInterface {
  /**
   * Interface for working with drupal module system.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * Constructs a new ImageAPICheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Interface for working with drupal module system.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('database')
    );
  }

  /**
   * The actual procedure of carrying out the check.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function perform() {
    $issues = [];
    $message = [];
    if (!$this->moduleHandler->moduleExists('dblog')) {
      return $this->skip($this->t('Module DBLog is not enabled.'));
    }

    $total = $this->getRowCount();
    if (!$total) {
      return $this->success();
    }

    $message[] = $total->render();
    if ($total) {
      $message[] = $total->render();
      if ($not_found = $this->getNotFoundCount()) {
        $issues['page_not_found'] = $not_found;
      }
      $message[] = $this->getAge()->render();
      if ($php = $this->getCountPhpErrors()) {
        $issues['php'] = $php;
      }
    }

    if (!empty($issues)) {
      return $this->fail(NULL, [
        'issues' => $issues,
        '@message' => implode(' ', $message),
      ]);
    }
    return $this->success();
  }

  /**
   * Calculate Page Not Found messages.
   */
  public function getNotFoundCount() {
    $count = $this->database->select('watchdog', 'w')
      ->fields('w')
      ->condition('w.type', 'page not found')
      ->countQuery()
      ->execute()
      ->fetchField();
    $count_rows = $this->database->select('watchdog', 'w')
      ->fields('w')
      ->countQuery()
      ->execute()
      ->fetchField();
    $percent = 0;
    if ($count) {
      $percent = round($count / $count_rows * 100);
    }
    if ($percent != 0) {
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
    $count_rows = $this->database->select('watchdog', 'w')
      ->fields('w')
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($count_rows) {
      return $this->t("There are @count_entries log entries.", [
        '@count_entries' => $count_rows,
      ]);
    }
    return FALSE;
  }

  /**
   * Get age of watchdog messages.
   */
  public function getAge() {
    // Age of oldest entry.
    $old = $this->database->select('watchdog', 'w')
      ->fields('w', ['timestamp'])
      ->orderBy('wid', 'ASC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    // Age of newest entry.
    $new = $this->database->select('watchdog', 'w')
      ->fields('w', ['timestamp'])
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    // If two different days...
    if (date('Y-m-d', $old) != date('Y-m-d', $new)) {
      return $this->t("Age of messages: From @from to @to (@days days)", [
        '@from' => date('Y-m-d', $old),
        '@to' => date('Y-m-d', $new),
        '@days' => round(($new - $old) / 86400, 2),
      ]);
    }
    // Same day; don't calculate number of days.
    return $this->t("Age of messages: From @from to @to", [
      '@from' => date('Y-m-d', $old),
      '@to' => date('Y-m-d', $new),
    ]);
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

    $php_total_count = $this->database->select('watchdog', 'w')
      ->fields('w')
      ->condition('w.type', 'php')
      ->countQuery()
      ->execute()
      ->fetchField();
    $count_rows = $this->database->select('watchdog', 'w')
      ->fields('w')
      ->countQuery()
      ->execute()
      ->fetchField();

    $severity_types = RfcLogLevel::getLevels();
    foreach ($severity_types as $key => $label) {
      $count_messages = $this->database->select('watchdog', 'w')
        ->fields('w')
        ->condition('w.severity', $key)
        ->countQuery()
        ->execute()
        ->fetchField();
      if ($count_messages) {
        $php_messages[] = $severity_types[$key] . ':' . $count_messages;
      }
    }

    $php_percent = round(($php_total_count / $count_rows) * 100, 2);

    return [
      '@issue_title' => 'PHP messages: @messages - total @percent %.',
      '@messages' => implode(', ', $php_messages),
      '@percent' => $php_percent,
    ];
  }

}
