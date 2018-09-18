<?php

namespace Drupal\adv_audit\Message;

/**
 * AuditMessagesStorageInterface Interface.
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
   * Set Method.
   */
  public function set($plugin_id, $value);

  /**
   * Get Method.
   */
  public function get($plugin_id, $type);

}
