<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Advances audit check plugins.
 */
abstract class AdvAuditCheckBase extends PluginBase implements AdvAuditCheckInterface {

  use StringTranslationTrait;

  /**
   * The state service object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateService;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Retrieves a configuration object.
   *
   * This is the main entry point to the configuration API. Calling
   * @code $this->config('book.admin') @endcode will return a configuration
   * object in which the book module can store its administrative settings.
   *
   * @param string $name
   *   The name of the configuration object to retrieve. The name corresponds to
   *   a configuration file. For @code \Drupal::config('book.admin') @endcode,
   *   the config object returned will contain the contents of book.admin
   *   configuration file.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  protected function config($name) {
    return $this->configFactory()->get($name);
  }

  /**
   * Gets the config factory for this form.
   *
   * When accessing configuration values, use $this->config(). Only use this
   * when the config factory needs to be manipulated directly.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected function configFactory() {
    if (!$this->configFactory) {
      $this->configFactory = $this->container()->get('config.factory');
    }
    return $this->configFactory;
  }

  /**
   * Sets the config factory for this form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * Resets the configuration factory.
   */
  public function resetConfigFactory() {
    $this->configFactory = NULL;
  }

  public function getMessage($type) {

  }

  /**
   * Return category id from plugin definition.
   *
   * @return mixed
   *   The Plugin category ID.
   */
  public function getCategoryName() {
    return $this->pluginDefinition['category'];
  }

  /**
   * Get category label value from config storage.
   *
   * @return array|mixed|null
   *   Return category label value.
   */
  public function getCategoryLabel() {
    // TODO: Not best implementation of getting config value. We should re-write this.
    return $this->config('adv_audit.config')->get('adv_audit_settings.categories' . $this->getCategoryName() . '.label');
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Get plugin severity level from config.
   *
   * @return mixed
   *   The severity level of plugin.
   */
  public function getSeverityLevel() {
    // Severity level can be overridden by plugin settings.
    $state = $this->getStateService();
    if ($state->has('adv_audit.plugin.severity.' . $this->getPluginId())) {
      // Return overridden severity for plugin.
      return $state->get('adv_audit.plugin.severity.' . $this->getPluginId());
    }
    // Return default severity from plugin definition.
    return $this->pluginDefinition['severity'];
  }

  public function setSeverityLevel($level) {
    $state = $this->getStateService();
    $state->set('adv_audit.plugin.severity.' . $this->getPluginId(), $level);
  }

  /**
   * Additional configuration form for plugin instance.
   * Value will be store in state storage and can be uses bu next key:
   *   - adv_audit.plugin.PLUGIN_ID.config.KEY
   *
   * @return array
   *    The form structure.
   */
  public function configForm() {
    return [];
  }

  /**
   * Config form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    // Check whether the current test plugin
    // requirements are met or not.
    if (!($this instanceof RequirementsInterface)) {
      return;
    }

    // TODO: Need to check the status of the plugin?

    if (empty($this->pluginDefinition['requirements'])) {
      // There are no requirements to check.
      return;
    }

    if (isset($this->pluginDefinition['requirements']['module'])) {
      /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
      $module_handler = $this->container()->get('module_handler');
      // Check what needed module are enabled.
      foreach ($this->pluginDefinition['requirements']['module'] as $module_name) {
        if (!$module_handler->moduleExists($module_name)) {
          throw new RequirementsException('Module ' . $module_name . ' are not enabled.', $this->pluginDefinition['requirements']['module']);
        }
      }
    }
  }

  /**
   * Check what plugin is enabled.
   *
   * @return bool
   *   Return TRUE if plugin are enabled, otherwise FALSE.
   */
  public function isEnabled() {
    return $this->getStatus() == TRUE;
  }

  /**
   * Get status for plugin.
   *
   * @return bool
   *   Return status for plugin.
   */
  public function getStatus() {
    // Status can be overridden by plugin settings.
    $state = $this->getStateService();
    if ($status = $state->get('adv_audit.plugin.enabled.' . $this->getPluginId())) {
      // Return overridden status for plugin.
      return $status;
    }
    // Return default status from plugin definition.
    return $this->pluginDefinition['enabled'];
  }

  /**
   * Override plugin status from settings.
   *
   * @param bool $status
   *   New status for plugin.
   */
  public function setPluginStatus($status = TRUE) {
    $state = $this->getStateService();
    $state->set('adv_audit.plugin.status.' . $this->getPluginId(), $status);
  }

  /**
   * Get State service.
   *
   * @return \Drupal\Core\State\StateInterface|object
   *   Return state service object.
   */
  private function getStateService() {
    if (!$this->stateService) {
      $this->stateService = $this->container()->get('state');
    }
    return $this->stateService;
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

}
