<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
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
   * Default administrator's username.
   */
  const DEFAULT_ADMIN_NAME = 'admin';

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

    // Get host.
    $parsed_base = parse_url($base_url);
    $host_parts = explode('.', $parsed_base['host']);

    // Check Admin name for host parts.
    foreach ($host_parts as $part) {
      if (stripos($admin_name, $part) !== FALSE) {
        $issue_details['has_host_parts'][] = $part;
      }
    }

    // Insecure admin name.
    if ($admin_name == self::DEFAULT_ADMIN_NAME) {
      $issue_details['has_default_admin_name'] = TRUE;
    }
    // The username contains "admin".
    elseif (stripos($admin_name, self::DEFAULT_ADMIN_NAME) !== FALSE) {
      $issue_details['has_admin_parts'] = TRUE;
    }

    if (empty($issue_details)) {
      return $this->success();
    }

    $issue_details['%name'] = $admin_name;
    return $this->fail(NULL, $issue_details);
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    $items = [];
    if ($type != AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      return [];
    }

    $issue_details = $reason->getArguments();
    if (!empty($issue_details['has_host_parts'])) {
      $items[] = 'There are host parts in admin username: ' . implode(', ', $issue_details['has_host_parts']);
    }
    if ($issue_details['has_default_admin_name']) {
      $items[] = "Using default name `admin` for superuser is highly insecure.";
    }
    elseif ($issue_details['has_admin_parts']) {
      $items[] = 'There is "admin" word in superuser name.';
    }

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Current name of admin is %name', $issue_details),
      '#list_type' => 'ol',
      '#items' => $items,
    ];
  }

}
