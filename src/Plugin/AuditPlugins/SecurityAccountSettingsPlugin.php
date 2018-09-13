<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if Account settings are OK for production.
 *
 * @AuditPlugin(
 *   id = "account_settings",
 *   label = @Translation("Check Account settings"),
 *   category = "security",
 *   severity = "normal",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class SecurityAccountSettingsPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  /**
   * Configuration container.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Possible variants of account settings.
   *
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ConfigFactoryInterface $factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $factory;
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
    $this->options = [
      USER_REGISTER_ADMINISTRATORS_ONLY => $this->t('Administrators only'),
      USER_REGISTER_VISITORS => $this->t('Visitors'),
      USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL => $this->t('Visitors, but administrator approval is required'),
    ];

    $current = $this->configFactory->get('user.settings')->get('register');

    $link = Link::createFromRoute($this->t('settings'), 'entity.user.admin_form', [], ['absolute' => TRUE])->toString();

    $placeholders['link'] = $link;
    $placeholders['current'] = $this->options[$current];

    $issues = [
      'account_settings' => [
        '@issue_title' => 'Visitors can register without administrator\'s approval.',
      ],
    ];

    if ($current === USER_REGISTER_VISITORS) {
      return $this->fail(NULL, [
        'issues' => $issues,
        '%link' => $placeholders['link'],
        '%current' => $placeholders['current'],
      ]);
    }
    return $this->success([
      '%link' => $placeholders['link'],
      '%current' => $placeholders['current'],
    ]);
  }

}
