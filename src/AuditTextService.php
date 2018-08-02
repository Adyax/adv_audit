<?php

namespace Drupal\adv_audit;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AuditMessagesService.
 */
class AuditTextService {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Config\StorageInterface definition.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $advAuditMessageStorage;

  protected $collections;

  /**
   * Constructs a new AuditMessagesService object.
   */
  public function __construct(StorageInterface $adv_audit_message_storage) {
    $this->advAuditMessageStorage = $adv_audit_message_storage;
    $this->collections = $this->advAuditMessageStorage->read('messages');
  }

  public function set($plugin_id, $type, $string) {
  }

  public function get($plugin_id, $type) {
  }

  public function getMessageByStatus($plugin_id, $status) {

  }

  public function getTranslated($plugin_id, $type, $options) {
    $string = $this->get($plugin_id, $type);
    if ($string) {
      return $this->t($string, $options);
    }
    return '';
  }

}
