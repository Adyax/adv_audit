<?php

namespace Drupal\adv_audit\Message;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AuditMessagesStorage.
 */
class AuditMessagesStorage implements AuditMessagesStorageInterface {

  use StringTranslationTrait;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $configFactory;

  /**
   * AuditMessagesStorage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Provide access to ConfigFactoryInterface.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function set($plugin_id, $value) {
    $configs = $this->configFactory->getEditable($this->getConfigKey($plugin_id));
    $data = $configs->getRawData();
    $data['messages'] = $value;
    $configs->set('messages', $data['messages'])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function get($plugin_id, $type) {
    $values = $this->configFactory->get($this->getConfigKey($plugin_id))->get('messages');
    if (!isset($values[$type])) {
      return NULL;
    }
    return $values[$type];
  }

  /**
   * Return plugins config key.
   *
   * @param string $pluginId
   *   String audit plugin id.
   *
   * @return string
   *   Plugin's config name.
   */
  private function getConfigKey($pluginId) {
    return 'adv_audit.plugins.' . $pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function replacePlaceholder($plugin_id, $type, $args) {
    $string = $this->get($plugin_id, $type);
    return new FormattableMarkup($string, $args);
  }

}
