<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\user\PermissionHandler;

/**
 * Check permission of untrusted roles.
 *
 * @AuditPlugin(
 *  id = "untrusted_roles_permission",
 *  label = @Translation("Untrusted role's permission"),
 *  category = "security",
 *  requirements = {},
 * )
 */
class SecurityUntrastedRolesPermissionsPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;
  /**
   * The audit messages storage service.
   *
   * @var \Drupal\adv_audit\Message\AuditMessagesStorageInterface
   */
  protected $messagesStorage;

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
   * @param \Drupal\adv_audit\Message\AuditMessagesStorageInterface $messages_storage
   *   Interface for the audit messages.
   * @param \Drupal\user\PermissionHandler $user_permission
   *   Provide access to user permissions.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AuditMessagesStorageInterface $messages_storage, PermissionHandler $user_permission) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messagesStorage = $messages_storage;
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
      $container->get('adv_audit.messages'),
      $container->get('user.permissions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $settings = $this->getSettings();
    $all_permissions = $this->userPermission->getPermissions();
    $all_permission_strings = array_keys($all_permissions);
    $untrusted_permissions = $this->rolePermissions(array_filter($settings['untrusted_roles']), TRUE);
    $arguments = [];
    foreach ($untrusted_permissions as $rid => $permissions) {
      $intersect = array_intersect($all_permission_strings, $permissions);
      foreach ($intersect as $permission) {
        if (isset($all_permissions[$permission]['restrict access'])) {
          $arguments['issues'][$rid . ' ' . $permissions] = [
            '@issue_title' => '@role has permission "@permission"',
            '@role' => $rid,
            '@permission' => $permission,
          ];
        }
      }
    }

    if (isset($arguments['issues']) && !empty($arguments['issues'])) {
      return $this->fail(NULL, $arguments);
    }
    return $this->success();
  }

  /**
   * Gets all the permissions.
   *
   * @param bool $meta
   *   Whether to return only permission strings or metadata too.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   *
   * @return array
   *   Array of every permission.
   */
  protected function permissions($meta = FALSE) {
    // Not injected because of hard testability.
    $permissions = $this->userPermission->getPermissions();

    if (!$meta) {
      return array_keys($permissions);
    }
    return $permissions;
  }

  /**
   * Returns the permission strings that a group of roles have.
   *
   * @param string[] $role_ids
   *   The array of roleIDs to check.
   * @param bool $group_by_role_id
   *   Choose whether to group permissions by role ID.
   *
   * @return array
   *   An array of the permissions untrusted roles have. If $groupByRoleId is
   *   true, the array key is the role ID, the value is the array of permissions
   *   the role has.
   */
  protected function rolePermissions(array $role_ids, $group_by_role_id = FALSE) {

    // Get the permissions the given roles have, grouped by roles.
    $permissions_grouped = user_role_permissions($role_ids);

    // Fill up the administrative roles' permissions too.
    foreach ($role_ids as $role_id) {
      $role = Role::load($role_id);
      if ($role->isAdmin()) {
        $permissions_grouped[$role_id] = $this->permissions();
      }
    }

    if ($group_by_role_id) {
      // If the result should be grouped, we have nothing else to do.
      return $permissions_grouped;
    }
    else {
      // Merge the grouped permissions into $untrusted_permissions.
      $untrusted_permissions = [];
      foreach ($permissions_grouped as $permissions) {
        $untrusted_permissions = array_merge($untrusted_permissions, $permissions);
      }

      return array_values(array_unique($untrusted_permissions));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    // Get the user roles.
    $roles = user_roles();
    $options = [];
    foreach ($roles as $rid => $role) {
      $options[$rid] = $role->label();
    }

    $form['untrusted_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Untrusted roles.'),
      '#default_value' => $settings['untrusted_roles'],
      '#options' => $options,
    ];

    return $form;
  }

}
