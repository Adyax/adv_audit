<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Plugin\AuditBasePlugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Check Views cache settings.
 *
 * @AuditPlugins(
 *  id = "performance_views",
 *  label = @Translation("Views performance settings"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class PerformanceViewsPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

  /**
   * Length of the day in seconds.
   */
  const ALLOWED_LIFETIME = 60;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Array of reasons with views without cache settings.
   *
   * @var array
   */
  protected $withoutCache;

  /**
   * Array of reasons with views with unknown cache type.
   *
   * @var array
   */
  protected $warnings;

  /**
   * Constructs a new PerformanceViewsPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->withoutCache = [];
    $this->warnings = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $views = $this->entityTypeManager->getListBuilder('view')->load();

    foreach ($views['enabled'] as $view) {
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display_name => $display) {

        if ($display->isEnabled()) {
          $this->auditDisplayCache($display, $display_name, $view);
        }
      }
    }

    if (count($this->withoutCache)) {
      return $this->fail(NULL, ['issues' => $this->withoutCache]);
    }
    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form['minimum_cache_lifetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache minimum age allowed in seconds'),
      '#default_value' => isset($settings['minimum_cache_lifetime']) ? $settings['minimum_cache_lifetime'] : static::ALLOWED_LIFETIME,
    ];

    return $form;
  }

  /**
   * Calculate minimum cache time for display cache options.
   *
   * @param array $cache
   *   Display cache options.
   *
   * @return int
   *   Minimum cache lifetime.
   */
  protected function getMinimumCacheTime(array $cache) {
    if (!empty($cache['options'])) {
      $results_lifespan = $cache['options']['results_lifespan'] !== 0 ? $cache['options']['results_lifespan'] : $cache['options']['results_lifespan_custom'];
      $output_lifespan = $cache['options']['output_lifespan'] !== 0 ? $cache['options']['output_lifespan'] : $cache['options']['output_lifespan_custom'];
      return $results_lifespan < $output_lifespan ? $results_lifespan : $output_lifespan;
    }
    return -1;
  }

  /**
   * Audit view display cache.
   */
  protected function auditDisplayCache($display, $display_name, $view) {
    // Exclude views with admin path.
    if (isset($display->options['path']) && strpos($display->options['path'], 'admin/') !== FALSE) {
      return;
    }

    $cache = $display->getOption('cache');
    if (empty($cache) || $cache['type'] == 'none') {
      $this->withoutCache[$view->id() . '.' . $display_name] = [
        '@issue_title' => 'Display @display_name of view @view_id has wrong cache settings.',
        '@view_id' => $view->id(),
        '@display_name' => $display_name,
      ];
    }
    elseif (in_array($cache['type'], ['time', 'search_api_time'])) {
      $minimum = $this->getMinimumCacheTime($cache);
      $settings_minimum = $this->getSettings();
      $settings_minimum = isset($settings_minimum['minimum_cache_lifetime']) ? $settings_minimum['minimum_cache_lifetime'] : static::ALLOWED_LIFETIME;
      if ($minimum < $settings_minimum) {
        $this->withoutCache[$view->id() . '.' . $display_name] = [
          '@issue_title' => 'Display @display_name of view @view_id cache minimum lifetime is less then allowed @allowed',
          '@view_id' => $view->id(),
          '@display_name' => $display_name,
        ];
      }
    }
  }

}
