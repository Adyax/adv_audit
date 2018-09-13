<?php

namespace Drupal\adv_audit\Message;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AuditMessagesStorage.
 */
class AuditMessagesStorage implements AuditMessagesStorageInterface {

  use StringTranslationTrait;

  /**
   * The default key for store messages via state api.
   */
  const STATE_STORAGE_KEY = 'adv_audit.messages';

  /**
   * Drupal\Core\Config\StorageInterface definition.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $advAuditMessageStorage;

  /**
   * The collection of plugin text.
   *
   * @var array
   */
  protected $collections;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AuditMessagesService object.
   */
  public function __construct(StorageInterface $adv_audit_message_storage, StateInterface $state, ConfigFactoryInterface $config_factory) {
    $this->advAuditMessageStorage = $adv_audit_message_storage;
    $this->state = $state;
    $this->configFactory = $config_factory;
    // Try to load already saved messages via State storage.
    $this->collections = $this->state->get(static::STATE_STORAGE_KEY, []);
    // Merge new values with already overriden.
    $this->collections = NestedArray::mergeDeep($this->advAuditMessageStorage->read(static::COLLECTION_NAME), $this->collections);
  }

  /**
   * Save value of message type.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $type
   *   The message type.
   * @param mixed $value
   *   New value for message type.
   */
  public function set($plugin_id, $value) {
    $configs = $this->configFactory->getEditable($this->getConfigKey($plugin_id));
    $data = $configs->getRawData();
    $data['messages'] = $value;
    $configs->set('messages', $data['messages'])->save();
  }

  /**
   * Return plugins config key.
   *
   * @param $pluginId
   *   String audit plugin id.
   *
   * @return string
   *   Plugin's config name.
   */
  private function getConfigKey($pluginId) {
    return 'adv_audit.plugins.' . $pluginId;
  }

  /**
   * Get value for plugin by message type.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param string $type
   *   The message type.
   *
   * @return null|string
   *   Return message string.
   */
  public function get($plugin_id, $type) {
    if (!isset($this->collections['plugins'][$plugin_id][$type])) {
      return NULL;
    }
    return $this->collections['plugins'][$plugin_id][$type];
  }

  /**
   * {@inheritdoc}
   */
  public function replacePlaceholder($plugin_id, $type, $args) {
    $string = $this->get($plugin_id, $type);

    return new FormattableMarkup($string, $args);
  }

}
