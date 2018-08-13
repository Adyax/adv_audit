<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;

/**
 * @AdvAuditCheck(
 *  id = "performance_views",
 *  label = @Translation("Views performance settings"),
 *  category = "performance",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class PerformanceViewsCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {
  
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
   * The State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * Constructs a new PerformanceViewsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, StateInterface $state,  EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->state = $state;
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
      $container->get('state'),
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
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, $this->withoutCache);
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.config.minimum_cache_lifetime';
  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {
    $form['minimum_cache_lifetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache minimum age allowed in secconds'),
      '#default_value' => $this->state->get($this->buildStateConfigKey())
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    $value = $form_state->getValue(['additional_settings', 'plugin_config', 'minimum_cache_lifetime'], self::ALLOWED_LIFETIME);
    $this->state->set($this->buildStateConfigKey(), $value);
  }

  /**
   * Calculate minimum cache time for display cache options.
   *
   * @param array $cache
   *   Display cache options.
   *
   * @return integer Minimum cache lifetime.
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
    $cache = $display->getOption('cache');

    $this->auditDisplayCache($display);
    if (empty($cache) || $cache['type'] == 'none') {
      $this->withoutCache[] = $this->t('Display @display_name of view @view_id has wrong cache settings.', [
        '@display_name' => $display_name,
        '@view_id' => $view->id(),
      ]);
    }
    elseif (in_array($cache['type'], ['time', 'search_api_time'])) {
      $minimum = $this->getMinimumCacheTime($cache);
      if ($minimum < $this->state->get($this->buildStateConfigKey(), self::ALLOWED_LIFETIME)) {
        $this->withoutCache[] = $this->t('Display @display_name of view @view_id cache minimum lifetime is less then allowed @allowed', [
          '@display_name' => $display_name,
          '@view_id' => $view->id(),
          '@allowed' => $this->state->get($this->buildStateConfigKey(), self::ALLOWED_LIFETIME),
        ]);
      }
    }
  }

}
