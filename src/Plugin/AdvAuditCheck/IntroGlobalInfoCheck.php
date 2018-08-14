<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
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
class IntroGlobalInfoCheck extends AdvAuditCheckBase implements  AdvAuditCheckInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {

    // Info data for render in own template.
    $renderData = array();

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    $totalUsers = $user_storage->getQuery()
      ->count()
      ->execute();
    $renderData['total_users'] = $totalUsers;

    $blockedUsers = $user_storage->getQuery()
      ->condition('status', 0)
      ->count()
      ->execute();
    $renderData['blocker_users'] = $blockedUsers;

    $uid1 = $user_storage->loadMultiple([1]);
    $renderData['uid1'] = [
      'name' => $uid1[1]->name->value,
      'email' => $uid1[1]->mail->value,
      'status' => $uid1[1]->isBlocked() ? 'blocked' : 'not blocked',
    ];

    $rolesList = Role::loadMultiple();
    foreach ($rolesList as $role => $value) {
      $renderData['roles_list'][] = $role;
    }


    $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator('./'), \RecursiveIteratorIterator::SELF_FIRST);
    $countFiles = 0;
    $filesTotalSize = 0;
    foreach ($objects as $name => $object) {
      if (strpos($name, 'vendor')) {
        continue;
      }
      $filesTotalSize += filesize($name);
      $countFiles++;
    }
    $renderData['count_files'] = $countFiles;
    $renderData['files_total_size'] = $filesTotalSize / 1048576;

    $connection = \Drupal::database();
    $query = $connection->query(
      "SELECT table_schema \"db_name\", Round(Sum(data_length + index_length) / 1024 / 1024, 1) \"db_size\" 
      FROM   information_schema.tables
      GROUP  BY table_schema;"
    );
    $result = $query->fetchAll();

    $resultDB = $connection->query(
      "SELECT table_schema FROM information_schema.tables WHERE table_name = 'sessions'"
    )
      ->fetchAll();
    $currentDB = $resultDB[0]->table_schema;

    $dbSize = 0;
    foreach ($result as $db) {
      if ($db->db_name == $currentDB) {
        $dbSize = $db->db_size;
      }
    }



    //$settingDB = $databases['default']['default']



    if (FALSE) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
  }


}
