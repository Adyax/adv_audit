<?php

namespace Drupal\adv_audit\Message;

use Drupal\Core\Config\FileStorage;

/**
 * Provides a factory for creating config file storage objects.
 */
class MessageStorageFactory {

    /**
     * Returns a FileStorage object working with the sync config directory.
     *
     * @return \Drupal\Core\Config\FileStorage FileStorage
     */
    public static function getMsgDir() {
        return new FileStorage(drupal_get_path('module', 'adv_audit') . '/config/messages');
    }

}
