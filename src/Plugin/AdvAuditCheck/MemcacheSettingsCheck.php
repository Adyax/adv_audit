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
 *  id = "memcache_settings",
 *  label = @Translation("Memcache/Redis settings"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class MemcacheSettingsCheck extends AdvAuditCheckBase implements  AdvAuditCheckInterface, ContainerFactoryPluginInterface {

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

    // Testing memcached.
    $memcache = $this->settings->get('memcache');
    if ($cache_default == 'cache.backend.memcache' && isset($memcache)) {

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
          $this->t('No memcached extension found'));
      }

      // Test server connections.
      if ($extension) {
        foreach ($memcache['servers'] as $address => $bin) {
          list($ip, $port) = explode(':', $address);
          if ($extension == 'Memcache') {
            if (!memcache_connect($ip, $port)) {
              return new AuditReason($this->id(),
                AuditResultResponseInterface::RESULT_FAIL,
                $this->t('Cannot connect to Memcache'));
            }
          }
          elseif ($extension == 'Memcached') {
            $m = new Memcached();
            $m->addServer($ip, $port);
            if ($m->getVersion() == FALSE) {
              return new AuditReason($this->id(),
                AuditResultResponseInterface::RESULT_FAIL,
                $this->t('Cannot connect to Memcached'));
            }
          }
        }
      }

      return new AuditReason($this->id(),
        AuditResultResponseInterface::RESULT_PASS);
    }

    // Testing redis.
    $redis_connection = $this->settings->get('redis.connection');
    if ($cache_default == 'cache.backend.redis' && isset($redis_connection)) {
      $redis_connection_interface = !empty($redis_connection['interface']) ?: NULL;
      switch ($redis_connection_interface) {
        case 'Predis':
          $extension = 'Predis\Client';
          break;

        default:
          $extension = 'Redis';
          break;
      }

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
        AuditResultResponseInterface::RESULT_PASS);
    }

    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL,
      $this->t('No memcached or redis extension found'));
  }

}
