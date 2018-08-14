<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Database\Connection;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @AdvAuditCheck(
 *  id = "intro_global_info_check",
 *  label = @Translation("Introduction, general results and global info"),
 *  category = "other",
 *  severity = "normal",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class IntroGlobalInfoCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager, Connection $connection, DrupalKernel $kernel, $root) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->kernel = $kernel;
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
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
    $errors = [];

    $renderData = $this->getUsersInfo();

    $rolesList = Role::loadMultiple();
    foreach ($rolesList as $role => $value) {
      $renderData['roles_list'][] = $role;
    }

    $renderData['filesystem_info'] = $this->getFilesystemInfo();

    $renderData['db_size'] = $this->getDatabaseSize();

    if (!empty($errors)) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
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

}
