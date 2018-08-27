<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\user\PermissionHandler;

/**
 * Check permission for anonymous.
 *
 * @AdvAuditCheck(
 *  id = "anonymous_user_permission",
 *  label = @Translation("Anonymous user rights"),
 *  category = "security",
 *  severity = "high",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class AnonymousPermission extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  const ANONYMOUS_ID = 'anonymous';

  /**
   * The State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * Provide access to renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new PerformanceViewsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   Access to state service.
   * @param \Drupal\adv_audit\Message\AuditMessagesStorageInterface $messages_storage
   *   Interface for the audit messages.
   * @param \Drupal\user\PermissionHandler $user_permission
   *   Provide access to user permissions.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Provide access to render service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, AuditMessagesStorageInterface $messages_storage, PermissionHandler $user_permission, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->messagesStorage = $messages_storage;
    $this->userPermission = $user_permission;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('adv_audit.messages'),
      $container->get('user.permissions'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $anonymous_permissions = $this->rolePermissions(self::ANONYMOUS_ID);
    $status = AuditResultResponseInterface::RESULT_PASS;
    $message = NULL;
    $arguments = [];
    $wrong_permission = [];
    foreach ($anonymous_permissions as $permission) {
      if (preg_match('/(\baccess\sall\b|\badd\b|\badminister\b|\bchange\b|\bclear\b|\bcreate\b|\bdelete\b|\bedit\b|\brevert\b|\bsave\b|\bsend\smail\b|\bset\svariable\b|\bupdate\b|\bupload\b|\bPHP\b|\bdevel\b)/i', $permission)) {
        $wrong_permission[] = $permission;
      }
    }
    if (!empty($wrong_permission)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $permissions = [
        '#theme' => 'item_list',
        '#items' => $wrong_permission,
      ];
      $arguments['%list'] = $this->renderer->render($permissions);
    }
    return new AuditReason($this->id(), $status, $message, $arguments);
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

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.additional-settings';
  }

}
