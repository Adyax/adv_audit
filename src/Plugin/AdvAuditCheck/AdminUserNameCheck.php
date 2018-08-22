<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for check Administrator's name.
 *
 * @AdvAuditCheck(
 *   id = "admin_name_check",
 *   label = @Translation("Administrator's name check"),
 *   category = "security",
 *   severity = "normal",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class AdminUserNameCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

  /**
   * Placeholder in messages.yml.
   */
  const NAME_PLACEHOLDER = '%name';

  /**
   * Default administrator's username.
   */
  const ADMIN_NAME = 'admin';

  /**
   * Host parts key.
   */
  const HAS_HOST = 'has_host_parts';

  /**
   * Entity type manager container.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    global $base_url;

    // Get admin's name.
    $user = $this->entityTypeManager->getStorage('user')->load(1);
    $admin_name = $user->get('name')->value;
    $secure = TRUE;

    $arguments = [
      self::NAME_PLACEHOLDER => $admin_name,
    ];

    // Get host.
    $parsed_base = parse_url($base_url);
    $host_parts = explode('.', $parsed_base['host']);

    foreach ($host_parts as $part) {
      if (stripos($admin_name, $part) !== FALSE) {
        $secure = FALSE;
        $arguments[self::HAS_HOST][] = $part;
      }
    }

    // The username contains "admin".
    if (stripos($admin_name, self::ADMIN_NAME) !== FALSE) {
      $secure = FALSE;
      $arguments['has_admin_parts'] = TRUE;
    }

    // Very bad variant for administrator's name.
    if ($admin_name == self::ADMIN_NAME) {
      $arguments['has_default_admin_name'] = TRUE;
    }

    if (!$secure) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, NULL, $arguments);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    $items = [];

    $arguments = $reason->getArguments();

    if ($type == AuditMessagesStorageInterface::MSG_TYPE_FAIL) {

      if ($arguments[self::HAS_HOST]) {
        $items[] = 'There are host parts in admin username: ' . implode(', ', $arguments[self::HAS_HOST]);
      }
      if ($arguments['has_default_admin_name']) {
        $items[] = 'Admin\'s username seems like default username for administrator';
      }
      if ($arguments['has_admin_parts'] && $arguments[self::NAME_PLACEHOLDER] != self::ADMIN_NAME) {
        $items[] = 'There are "admin" parts in username';
      }

      $build['admin_name_check'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Current name of admin is %name', ['%name' => $arguments[self::NAME_PLACEHOLDER]]),
        '#list_type' => 'ol',
        '#items' => $items,
      ];
      return $build;
    }
    return [];
  }

}
