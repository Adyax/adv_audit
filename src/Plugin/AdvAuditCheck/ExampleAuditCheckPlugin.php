<?php
/**
 * @file
 * Provide example of Adv audit plugin.
 */

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

/**
 * @AdvAuditCheck(
 *  id = "adv_audit_check_example",
 *  label = @Translation("Example plugin"),
 *  category = "other",
 *  severity = "low",
 *  enabled = true,
 *  requirements = {
      "module": {"node"}
 *   },
 * )
 */
class ExampleAuditCheckPlugin extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The audit messages storage service.
   *
   * @var \Drupal\adv_audit\Message\AuditMessagesStorageInterface
   */
  protected $messagesStorage;

  /**
   * Constructs a new ExampleAuditCheckPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, AuditMessagesStorageInterface $messages_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->messagesStorage = $messages_storage;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('adv_audit.messages')
    );
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.config.check_should_passed';
  }

  /**
   * The actual procedure of carrying out the check.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function perform() {
    // Created link object and put in both result because this link used on other messages like actions
    $example_link = Link::createFromRoute('LINK', '<front>');
    if ($this->state->get($this->buildStateConfigKey()) == 1) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS, NULL, ['@random' => rand(1, 100), '%link' => $example_link->toString()]);
    }

    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, NULL, ['@hash' => md5('this is simple string'), '%link' => $example_link->toString()]);
  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {
    $form['check_should_passed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select this if check should passed'),
      '#default_value' => $this->state->get($this->buildStateConfigKey())
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    // Get value from form_state object and save it.
    $value = $form_state->getValue(['additional_settings', 'plugin_config', 'check_should_passed'], 0);
    $this->state->set($this->buildStateConfigKey(), $value);
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    // Please be careful.
    // Before extend check Requirements method you should call parent method before.
    parent::checkRequirements();
    // Check our custom requirements for plugin.
    if ($this->state->get('install_task') != 'done') {
      throw new RequirementsException('Human readable reason message.', ['install_task']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    switch ($type) {
      // Override messages fail output.
      // In this case, we will not use messages from messages.yml file and directly will render what you return.
      case AuditMessagesStorageInterface::MSG_TYPE_FAIL:
        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['custom-fail-color'],
          ],
          'message' => [
            '#markup' => $this->t($this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_FAIL), $reason->getArguments())->__toString(),
          ],
        ];
        break;
      // Override messages success output.
      // At this moment you have fully control what how will build success messages.
      case AuditMessagesStorageInterface::MSG_TYPE_SUCCESS:
        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['custom-pass-color'],
          ],
          'message' => [
            '#markup' => $this->t($this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_SUCCESS), $reason->getArguments())->__toString(),
          ],
        ];
        break;

      default:
        // Return empty array.
        // In this case will display messages from messages.yml file for you plugin.
        $build = [];
        break;
    }

    return $build;
  }

}
