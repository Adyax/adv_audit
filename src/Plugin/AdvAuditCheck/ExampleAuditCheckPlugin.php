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
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
class ExampleAuditCheckPlugin extends AdvAuditCheckBase implements ContainerFactoryPluginInterface{

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
    if ($this->state->get($this->buildStateConfigKey()) == 1) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS, NULL, ['@random' => rand(1, 100)]);
    }

    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, NULL, ['@hash' => md5('this is simple string')]);
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
    $value = $form_state->getValue(['additional_settings', 'plugin_config', 'check_should_passed'], 0);
    $this->state->set($this->buildStateConfigKey(), $value);
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
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
      case AuditMessagesStorageInterface::MSG_TYPE_FAIL:
        return [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['custom-fail-color'],
          ],
          'message' => [
            '#markup' => $this->t($this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_FAIL), $reason->getArguments())->__toString(),
          ],
        ];
        break;

      case AuditMessagesStorageInterface::MSG_TYPE_SUCCESS:
        return [
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
        break;
    }
    return [];
  }

}
