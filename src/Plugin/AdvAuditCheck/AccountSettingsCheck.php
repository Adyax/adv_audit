<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if Account settings are OK for production.
 *
 * @AdvAuditCheck(
 *   id = "account_settings",
 *   label = @Translation("Check Account settings"),
 *   category = "security",
 *   severity = "normal",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class AccountSettingsCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

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
    $issue_details['%current'] = $this->options[$current];

    $link = Link::createFromRoute($this->t('settings'), 'entity.user.admin_form')->toString();
    $issue_details['%link'] = $link;

    if ($current == USER_REGISTER_VISITORS) {
      return $this->fail(NULL, $issue_details);
    }
    return $this->success($issue_details);
  }

}
