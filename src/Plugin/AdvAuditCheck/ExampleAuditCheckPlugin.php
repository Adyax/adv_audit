<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * This example of plugin.
 *
 * This plugin described all features are available for checkpoint plugins.
 *
 * @AdvAuditCheck(
 *  id = "adv_audit_check_example",
 *  label = @Translation("Example plugin"),
 *  category = "other",
 *  severity = "low",
 *  enabled = true,
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
class ExampleAuditCheckPlugin extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ExampleAuditCheckPlugin object.
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
   * Method is optional.
   */
  public function configForm() {
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
  public function configFormSubmit(array $form, FormStateInterface $form_state) {
    // Get value from form_state object and save it.
    // In this place we can save our value from config form.
    $value = $form_state->getValue([
      'additional_settings',
      'plugin_config',
      'check_should_passed',
    ], 0);
    $value;
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

  /**
   * {@inheritdoc}
   *
   * Method is optional.
   *
   * NOTE:
   *   This method can be implemented if you should display table or something
   *   else like the numeric list instead of standard text message from
   *   message.yml file
   */
  public function auditReportRender(AuditReason $reason, $type) {
    switch ($type) {
      // Override messages fail output.
      // In this case, we will not use messages from messages.yml file and
      // directly will render what you return.
      case AuditMessagesStorageInterface::MSG_TYPE_FAIL:
        $build = [
          '#type' => 'container',
          'message' => [
            '#markup' => 'My custom HTML markup for display MSG_TYPE_FAIL message.',
          ],
        ];
        break;

      // Override messages success output.
      // At this moment you have fully control what how will build
      // success messages.
      case AuditMessagesStorageInterface::MSG_TYPE_SUCCESS:
        $build = [
          '#type' => 'container',
          'message' => [
            '#markup' => 'My custom HTML markup for display MSG_TYPE_SUCCESS message.',
          ],
        ];
        break;

      default:
        // Return empty array.
        // In this case all other messages will be displayed from messages.yml
        // file for you plugin.
        $build = [];
        break;
    }

    return $build;
  }

}
