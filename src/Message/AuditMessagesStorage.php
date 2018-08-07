<?php

namespace Drupal\adv_audit\Message;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AuditMessagesStorage.
 */
class AuditMessagesStorage implements AuditMessagesStorageInterface{

  use StringTranslationTrait;

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
   * Constructs a new AuditMessagesService object.
   */
  public function __construct(StorageInterface $adv_audit_message_storage) {
    $this->advAuditMessageStorage = $adv_audit_message_storage;
    $this->collections = $this->advAuditMessageStorage->read(static::COLLECTION_NAME);
  }

  /**
   * Save value of message type.
   *
   * @param $plugin_id
   *   The plugin id.
   * @param $type
   *   The message type.
   * @param $string
   *   New value for message type.
   */
  public function set($plugin_id, $type, $string) {
    $this->collections['plugins'][$plugin_id][$type] = $string;
    $this->advAuditMessageStorage->write(static::COLLECTION_NAME, $this->collections);

  }

  /**
   * Get value for plugin by message type.
   *
   * @param $plugin_id
   *   The plugin id.
   * @param $type
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
   * Get translated string object.
   *
   * @param $plugin_id
   *   The plugin id.
   * @param $type
   *   The message type.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'langcode' (defaults to the current language): A language code, to
   *     translate to a language other than what is used to display the page.
   *   - 'context' (defaults to the empty context): The context the source
   *     string belongs to. See the
   *     @link i18n Internationalization topic @endlink for more information
   *     about string contexts.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An object that, when cast to a string, returns the translated string.
   */
  public function getTranslated($plugin_id, $type, $options) {
    $string = $this->get($plugin_id, $type);
    if ($string) {
      return $this->t($string, $options);
    }
  }

}
