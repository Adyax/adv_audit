<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;

/**
 * Backend Cache Check plugin class.
 *
 * @AdvAuditCheck(
 *  id = "backend_cache_settings",
 *  label = @Translation("Memcache/Redis settings"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class BackendCacheSettingsCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * Backend cache list.
   */
  const RECOMMENDED_BACKEND_CACHE = [
    'cache.backend.memcache',
    'cache.backend.redis',
  ];

  /**
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a new CronSettingsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Site\Settings $settings
   *   Read only settings that are initialized with the class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Settings $settings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $cache_settings = $this->settings->get('cache');
    $cache_default = isset($cache_settings['default']) ? $cache_settings['default'] : 'cache.backend.database';

    if (in_array($cache_default, self::RECOMMENDED_BACKEND_CACHE)) {
      return $this->success();
    }

    return $this->fail(NULL, [
      'issues' => [
        'no_cache_settings' => [
          '@issue_title' => 'No memcached or redis cache used on the site.',
        ],
      ],
    ]);
  }

}
