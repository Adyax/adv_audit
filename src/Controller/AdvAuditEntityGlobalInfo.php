<?php

namespace Drupal\adv_audit\Controller;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Get globall info about project.
 */
class AdvAuditEntityGlobalInfo extends AdvAuditCheckBase implements ContainerInjectionInterface {
  /**
   * Entity Type Manager container.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Connection container.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  protected $root;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entity_type_manager, Connection $connection, DrupalKernel $kernel, $root) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->kernel = $kernel;
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('kernel'),
      $container->get('app.root')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {

    $renderData = $this->getUsersInfo();

    $renderData['roles_list'] = $this->getRolesList();

    $renderData['filesystem_info'] = $this->getFilesystemInfo();

    $renderData['db_size'] = $this->getDatabaseSize();

    return $this->auditReportRender(
      new AuditReason(
        $this->id(),
        AuditResultResponseInterface::RESULT_PASS,
        'NULL', $renderData
      ),
      'success'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    $build = [];
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_SUCCESS) {
      $build['global_info'] = [
        '#theme' => 'adv_audit_global_info',
        '#global_info' => $reason->getArguments(),
      ];
    }
    return $build;
  }

  /**
   * Get size of current Database.
   *
   * @return mixed
   *   Returns DB's size or false.
   */
  protected function getDatabaseSize() {
    $query = $this->connection->query(
      "SELECT table_schema \"db_name\", Round(Sum(data_length + index_length) / 1024 / 1024, 2) \"db_size\"
      FROM   information_schema.tables
      GROUP  BY table_schema;"
    );
    $result = $query->fetchAll();
    $currentDBName = $this->connection->getConnectionOptions()['database'];
    foreach ($result as $db) {
      if ($db->db_name == $currentDBName) {
        return $db->db_size;
      }
    }

    return FALSE;
  }

  /**
   * Get users info like total, blocked, admin data.
   *
   * @return array
   *   Users data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getUsersInfo() {
    $renderData = [];

    $user_storage = $this->entityTypeManager->getStorage('user');

    $totalUsers = $user_storage->getQuery()
      ->count()
      ->execute();
    $renderData['total_users'] = $totalUsers;

    $blockedUsers = $user_storage->getQuery()
      ->condition('status', 0)
      ->count()
      ->execute();
    $renderData['blocked_users'] = $blockedUsers;

    // Check if admin is not blocked.
    $uid1 = $user_storage->loadMultiple([1]);
    $renderData['uid1'] = [
      'name' => $uid1[1]->name->value,
      'email' => $uid1[1]->mail->value,
      'status' => $uid1[1]->isBlocked() ? 'blocked' : 'not blocked',
    ];

    return $renderData;
  }

  /**
   * Get info about filesystem Drupal managed.
   *
   * @return array
   *   Returns info about filesystem.
   */
  protected function getFilesystemInfo() {

    $path = $this->root . '/' . $this->kernel->getSitePath() . '/files';

    $iterator = new \RecursiveDirectoryIterator($path);
    $iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
    $objects = new \RecursiveIteratorIterator($iterator);

    // Counters.
    $countFiles = 0;
    $filesTotalSize = 0;

    foreach ($objects as $name => $object) {
      $filesTotalSize += filesize($name);
      $countFiles++;
    }

    $renderData['count_files'] = $countFiles;

    // Total size in MBytes.
    $renderData['files_total_size'] = round($filesTotalSize / 1048576, 2) . "MB";

    return $renderData;
  }

  /**
   * Get list of roles.
   *
   * @return array
   *   List of roles and the corresponding number of users.
   */
  protected function getRolesList() {
    $result = $this->connection->query(
      'SELECT roles_target_id AS name,
       COUNT(entity_id) AS count_users
       FROM {user__roles}
       GROUP BY name
       ORDER BY name ASC'
    )->fetchAll();
    foreach ($result as $row) {
      $renderData[$row->name] = $row->count_users;
    }
    return $renderData;
  }

}
