<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * This example of plugin.
 *
 * This plugin described all features are available for checkpoint plugins.
 *
 * Implementation PluginFormInterface is required only in case
 * if your plugin need some specific settings.
 *
 * @AuditPlugins(
 *  id = "adv_audit_check_example",
 *  label = @Translation("Example plugin"),
 *  category = "other",
 *  severity = "low",
 *  enabled = false,
 *  requirements = {
 *   "module": {
 *    "adminimal_admin_toolbar:1.0-dev",
 *   },
 *   "config": {
 *    "devel.settings",
 *   },
 *   "library": {
 *    "html2canvas",
 *   },
 *   "core": "8.3.5",
 *   "php": "7.1"
 *  },
 * )
 */
class OtherExampleAuditPluginPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new OtherExampleAuditPluginPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * The actual procedure of carrying out the check.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function perform() {
    // Generate random result.
    if (rand(0, 1) == 1) {
      return $this->success();
    }

    return $this->fail('Here provide the reason while the AuditCheck has FAILED.');
  }

  /**
   * {@inheritdoc}
   *
   * Method is required in case if your class implements PluginFormInterface.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)  {

    $form['check_should_passed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select this if check should passed'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Method is optional.
   */
  public function checkRequirements() {
    // Please be careful.
    // Before extend check Requirements method you should call
    // parent method before.
    parent::checkRequirements();
    // Check our custom requirements for plugin.
    if (rand(0, 10) < 2) {
      throw new RequirementsException('Human readable reason message.', ['install_task']);
    }
  }

}
