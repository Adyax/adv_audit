<?php

namespace Drupal\adv_audit\Message;

/**
 * An interface AuditMessagesStorageInterface.
 */
interface AuditMessagesStorageInterface {
  const MSG_TYPE_DESCRIPTION = 'description';
  const MSG_TYPE_ACTIONS     = 'actions';
  const MSG_TYPE_IMPACTS     = 'impacts';
  const MSG_TYPE_FAIL        = 'fail';
  const MSG_TYPE_SUCCESS     = 'success';
  const MSG_TYPE_HELP        = 'help';


  const COLLECTION_NAME = 'messages';

  /**
   * Save value of message type.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $value
   *   New value for message type.
   */
  public function set($plugin_id, $value);

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
  public function get($plugin_id, $type);

}
