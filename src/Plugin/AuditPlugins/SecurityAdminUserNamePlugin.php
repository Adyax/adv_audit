<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for check Administrator's name.
 *
 * @AuditPlugin(
 *   id = "admin_name_check",
 *   label = @Translation("Administrator's name check"),
 *   category = "security",
 *   requirements = {},
 * )
 */
class SecurityAdminUserNamePlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
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
    if ($admin_name == static::DEFAULT_ADMIN_NAME) {
      $issue_details['has_default_admin_name'] = TRUE;
    }
    // The username contains "admin".
    elseif (stripos($admin_name, static::DEFAULT_ADMIN_NAME) !== FALSE) {
      $issue_details['has_admin_parts'] = TRUE;
    }

    if (!empty($issue_details)) {
      $issues = [];

      if (!empty($issue_details['has_host_parts'])) {
        foreach ($issue_details['has_host_parts'] as $host_part) {
          $issues[$host_part] = [
            '@issue_title' => 'There is a host part in admin username: @part',
            '@part' => $host_part,
          ];
        }
      }
      if ($issue_details['has_default_admin_name']) {
        $issues['has_default_admin_name'] = [
          '@issue_title' => 'Using default name "@admin" for superuser is highly insecure.',
          '@admin' => $admin_name,
        ];
      }
      elseif ($issue_details['has_admin_parts']) {
        $issues['has_admin_parts'] = [
          '@issue_title' => 'There is "admin" word in superuser name @name.',
          '@name' => $admin_name,
        ];
      }

      return $this->fail(NULL, [
        'issues' => $issues,
        '%name' => $admin_name,
      ]);
    }

    return $this->success(['%name' => $admin_name]);
  }

}
