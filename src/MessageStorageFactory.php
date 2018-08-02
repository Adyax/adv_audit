<?php

namespace Drupal\adv_audit;

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
    public static function getSync() {
        return new FileStorage(__DIR__ . '/configs/messages');
    }

}
