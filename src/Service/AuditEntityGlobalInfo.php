<?php

namespace Drupal\adv_audit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Get global info about project.
 */
class AuditEntityGlobalInfo implements ContainerInjectionInterface {
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
   * The drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entity_type_manager, Connection $connection, DrupalKernel $kernel, $root, ModuleHandler $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->kernel = $kernel;
    $this->root = $root;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('kernel'),
      $container->get('app.root'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {

    $renderData = $this->getUsersInfo();

    $renderData['roles_list'] = $this->getRolesList();

    $renderData['filesystem_info'] = $this->getFilesystemInfo();

    return $renderData;
  }

  /**
   * Get users info like total, blocked, admin data.
   *
   * @return array
   *   Users data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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

    if ($this->moduleHandler->moduleExists('s3fs')) {
      return ['s3fs' => TRUE];
    }

    $path['public_stream'] = PublicStream::basePath();
    $path['private_stream'] = PrivateStream::basePath() ? PrivateStream::basePath() : FALSE;

    // Counters.
    $countFiles = 0;
    $filesTotalSize = 0;
    foreach ($path as $item) {
      if ($item) {
        $iterator = new \RecursiveDirectoryIterator($item);
        $iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $objects = new \RecursiveIteratorIterator($iterator);

        foreach ($objects as $name => $object) {
          $filesTotalSize += filesize($name);
          $countFiles++;
        }
      }
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
    $query = $this->connection->select('user__roles', 'ur');
    $query->addExpression('COUNT(ur.entity_id)', 'user_count');
    $query->fields('ur', ['roles_target_id']);
    $query->groupBy('ur.roles_target_id');
    $query->orderBy('ur.roles_target_id', 'ASC');
    $result = $query->execute()->fetchAll();
    $renderData = [];
    foreach ($result as $row) {
      $renderData[$row->roles_target_id] = $row->user_count;
    }
    return $renderData;
  }

}
