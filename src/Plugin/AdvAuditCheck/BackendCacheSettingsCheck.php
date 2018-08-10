<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;

/**
 * @AdvAuditCheck(
 *  id = "backend_cache_settings",
 *  label = @Translation("Memcache/Redis settings"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class BackendCacheSettingsCheck extends AdvAuditCheckBase implements  AdvAuditCheckInterface, ContainerFactoryPluginInterface {

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

    switch ($cache_default) {
      case 'cache.backend.memcache':
        $this->memcached_check();
        break;

      case 'cache.backend.redis':
        $this->redis_check();
        break;

      default:
        return new AuditReason($this->id(),
          AuditResultResponseInterface::RESULT_FAIL);
    }

  }

  /**
   * Check memcached connection.
   */
  private function memcached_check() {
    if (!$memcache = $this->settings->get('memcache')) {
      return new AuditReason($this->id(),
        AuditResultResponseInterface::RESULT_FAIL,
        $this->t('No memcached setting found')
      );
    }

    // Select PECL memcache/memcached library to use.
    $preferred_memcache_extension = $this->settings->get('memcache_extension', NULL);

    if (isset($preferred_memcache_extension) && class_exists($preferred_memcache_extension)) {
      $extension = $preferred_memcache_extension;
    }
    // If no extension is set, default to Memcache.
    elseif (class_exists('Memcache')) {
      $extension = 'Memcache';
    }
    elseif (class_exists('Memcached')) {
      $extension = 'Memcached';
    }
    else {
      return new AuditReason($this->id(),
        AuditResultResponseInterface::RESULT_FAIL,
        $this->t('No memcached extension found')
      );
    }

    // Test server connections.
    foreach ($memcache['servers'] as $address => $bin) {
      list($ip, $port) = explode(':', $address);
      if ($extension == 'Memcache' && !memcache_connect($ip, $port)) {
        return new AuditReason($this->id(),
          AuditResultResponseInterface::RESULT_FAIL,
          $this->t('Cannot connect to Memcache')
        );
      }
      elseif ($extension == 'Memcached') {
        $memcached = new Memcached();
        $memcached->addServer($ip, $port);
        if ($memcached->getVersion() == FALSE) {
          return new AuditReason($this->id(),
            AuditResultResponseInterface::RESULT_FAIL,
            $this->t('Cannot connect to Memcached')
          );
        }
      }
    }

    return new AuditReason($this->id(),
      AuditResultResponseInterface::RESULT_PASS,
      $this->t('Memcached configured properly')
    );

  }

  /**
   * Check redis connection.
   */
  private function redis_check() {
    if (!$redis_connection = $this->settings->get('redis.connection')) {
      return new AuditReason($this->id(),
        AuditResultResponseInterface::RESULT_FAIL,
        $this->t('No redis setting found')
      );
    }

    $redis_connection_interface = !empty($redis_connection['interface']) ?: NULL;
    $extension = $redis_connection_interface == 'Predis' ? 'Predis\Client' : 'Redis';

    if (class_exists($extension)) {
      if ($extension == 'Predis\Client') {
        $redis = new \Predis\Client([
          'host' => $redis_connection['host'],
          'port' => $redis_connection['port'],
          'timeout' => 0.8,
        ]);
      }
      else {
        $redis = new Redis();
        $redis->connect($redis_connection["host"], $redis_connection["port"]);
      }

      try {
        $redis->ping();
      }
      catch (Exception $e) {
        return new AuditReason($this->id(),
          AuditResultResponseInterface::RESULT_FAIL,
          $this->t('Cannot connect to Redis'));
      }
    }
    else {
      return new AuditReason($this->id(),
        AuditResultResponseInterface::RESULT_FAIL,
        $this->t('No redis extension found'));
    }

    return new AuditReason($this->id(),
      AuditResultResponseInterface::RESULT_PASS,
      $this->t('Redis configured properly'));

  }

}
