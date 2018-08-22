<?php

namespace Drupal\adv_audit;

use Drupal\Core\State\StateInterface;

/**
 * Class AdvAuditPluginConfigStorageService.
 */
class AdvAuditPluginConfigStorageService implements AdvAuditPluginConfigStorageServiceInterface {

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Flag determine about any changes in stored configs.
   *
   * @var bool
   */
  protected $isChanged;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Store configs value.
   *
   * @var mixed
   */
  protected $storedConfigs;

  /**
   * Key value where we save plugin configuration.
   *
   * @var string
   */
  protected $stateKey;

  /**
   * Constructs a new AdvAuditPluginConfigStorageService object.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
    // Load config
    $this->stateKey = 'adv_audit.plugin.' . $this->pluginId . '.configs';
    $this->storedConfigs = $this->state->get($this->stateKey, []);
    $this->isChanged = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    return isset($this->storedConfigs[$key]) ? $this->storedConfigs[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->isChanged = TRUE;
    $this->storedConfigs[$key] = $value;
    $this->state->set($this->stateKey, $this->storedConfigs);
  }

}
