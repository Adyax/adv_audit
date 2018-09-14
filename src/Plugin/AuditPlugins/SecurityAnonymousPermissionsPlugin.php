<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\PermissionHandler;

/**
 * Check permission for anonymous.
 *
 * @AuditPlugin(
 *  id = "anonymous_user_permission",
 *  label = @Translation("Anonymous user rights"),
 *  category = "security",
 *  severity = "high",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class SecurityAnonymousPermissionsPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  const ANONYMOUS_ID = 'anonymous';

  /**
   * Provide access to user permission service.
   *
   * @var \Drupal\user\PermissionHandler
   */
  protected $userPermission;

  /**
   * Constructs a new PerformanceViewsPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PermissionHandler $user_permission
   *   Provide access to user permissions.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionHandler $user_permission) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userPermission = $user_permission;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.permissions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $anonymous_permissions = $this->rolePermissions(static::ANONYMOUS_ID);
    $arguments = [];
    foreach ($anonymous_permissions as $permission) {
      if (preg_match('/(\baccess\sall\b|\badd\b|\badminister\b|\bchange\b|\bclear\b|\bcreate\b|\bdelete\b|\bedit\b|\brevert\b|\bsave\b|\bsend\smail\b|\bset\svariable\b|\bupdate\b|\bupload\b|\bPHP\b|\bdevel\b)/i', $permission)) {
        $arguments['issues'][$permission] = [
          '@issue_title' => $permission,
        ];
      }
    }
    if (!empty($arguments['issues'])) {
      return $this->fail(NULL, $arguments);
    }
    return $this->success();
  }

  /**
   * Returns the permission strings that a group of roles have.
   *
   * @param string $role_id
   *   The roleID to check.
   *
   * @return array
   *   An array of the permissions untrusted roles have. If $groupByRoleId is
   *   true, the array key is the role ID, the value is the array of permissions
   *   the role has.
   */
  protected function rolePermissions($role_id) {
    // Get the permissions the given roles have, grouped by roles.
    $permissions_grouped = user_role_permissions([$role_id]);
    return reset($permissions_grouped);
  }

}
