<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin checks if users table contains anonymous user.
 *
 * @AdvAuditCheck(
 *   id = "anonymous_user_table",
 *   label = @Translation("Check if users table contains anonymous user"),
 *   category = "security",
 *   severity = "high",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class AnonymousUserTableCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * Connection container.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $query = $this->connection->select('users', 'u');
    $query->condition('uid', '0', '=');
    $query->fields('u', ['uuid']);

    $result = $query->execute()->fetchAll();

    if (empty($result)) {
      return $this->fail();
    }
    return $this->success();
  }

}
