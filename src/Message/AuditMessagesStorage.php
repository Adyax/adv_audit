<?php

namespace Drupal\adv_audit\Message;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
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
   * {@inheritdoc}
   */
  public function getTranslated($plugin_id, $type, $options) {
    $string = $this->get($plugin_id, $type);
    if ($string) {
      return $this->t($string, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function replacePlaceholder($plugin_id, $type, $args) {
    $string = $this->get($plugin_id, $type);
    // Transform arguments before inserting them.
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        case '@':
          // Escape if the value is not an object from a class that implements
          // \Drupal\Component\Render\MarkupInterface, for example strings will
          // be escaped.
          // Strings that are safe within HTML fragments, but not within other
          // contexts, may still be an instance of
          // \Drupal\Component\Render\MarkupInterface, so this placeholder type
          // must not be used within HTML attributes, JavaScript, or CSS.
          $args[$key] = static::placeholderEscape($value);
          break;

        case ':':
          // Strip URL protocols that can be XSS vectors.
          $value = UrlHelper::stripDangerousProtocols($value);
          // Escape unconditionally, without checking whether the value is an
          // instance of \Drupal\Component\Render\MarkupInterface. This forces
          // characters that are unsafe for use in an "href" HTML attribute to
          // be encoded. If a caller wants to pass a value that is extracted
          // from HTML and therefore is already HTML encoded, it must invoke
          // \Drupal\Component\Render\OutputStrategyInterface::renderFromHtml()
          // on it prior to passing it in as a placeholder value of this type.
          // @todo Add some advice and stronger warnings.
          //   https://www.drupal.org/node/2569041.
          $args[$key] = Html::escape($value);
          break;

        case '%':
          // Similarly to @, escape non-safe values. Also, add wrapping markup
          // in order to render as a placeholder. Not for use within attributes,
          // per the warning above about
          // \Drupal\Component\Render\MarkupInterface and also due to the
          // wrapping markup.
          $args[$key] = '<em class="placeholder">' . static::placeholderEscape($value) . '</em>';
          break;

        default:
          // We do not trigger an error for placeholder that start with an
          // alphabetic character.
          // @todo https://www.drupal.org/node/2807743 Change to an exception
          //   and always throw regardless of the first character.
          if (!ctype_alpha($key[0])) {
            // We trigger an error as we may want to introduce new placeholders
            // in the future without breaking backward compatibility.
            trigger_error('Invalid placeholder (' . $key . ') in string: ' . $string, E_USER_ERROR);
          }
          elseif (strpos($string, $key) !== FALSE) {
            trigger_error('Invalid placeholder (' . $key . ') in string: ' . $string, E_USER_DEPRECATED);
          }
          // No replacement possible therefore we can discard the argument.
          unset($args[$key]);
          break;
      }
    }

    return strtr($string, $args);
  }

  /**
   * Escapes a placeholder replacement value if needed.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $value
   *   A placeholder replacement value.
   *
   * @return string
   *   The properly escaped replacement value.
   */
  protected static function placeholderEscape($value) {
    return $value instanceof MarkupInterface ? (string) $value : Html::escape($value);
  }

}
