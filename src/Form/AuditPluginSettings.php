<?php

namespace Drupal\adv_audit\Form;

use Drupal\adv_audit\AuditExecutable;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Message\AuditMessageCapture;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides implementation for the Run form.
 */
class AuditPluginSettings extends FormBase {

  /**
   * Advanced plugin manager.
   *
   * @var \Drupal\adv_audit\Plugin\AdvAuditCheckManager
   */
  protected $advAuditPluginManager;

  /**
   * The Messages storeage service.
   *
   * @var \Drupal\adv_audit\Message\AuditMessagesStorageInterface
   */
  protected $messageStorage;

  /**
   * THe current request object.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The plugin id.
   *
   * @var mixed
   */
  protected $pluginId;

  /**
   * The plugin instance.
   *
   * @var \Drupal\adv_audit\Plugin\AdvAuditCheckBase
   */
  protected $pluginInstance;

  /**
   * AdvAuditPluginSettings constructor.
   *
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckManager $manager
   *   Manager plugins for advanced auditor.
   * @param \Drupal\adv_audit\Message\AuditMessagesStorageInterface $storage_message
   *   Custom storage for messages.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack that controls the lifecycle of requests.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __construct(AdvAuditCheckManager $manager, AuditMessagesStorageInterface $storage_message, RequestStack $request_stack) {
    $this->advAuditPluginManager = $manager;
    $this->messageStorage = $storage_message;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->pluginId = $request_stack->getCurrentRequest()->attributes->get('plugin_id');
    $this->pluginInstance = $this->advAuditPluginManager->createInstance($this->pluginId);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.adv_audit_check'),
      $container->get('adv_audit.messages'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced-audit-edit-plugin';
  }

  /**
   * Get title of config form page.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Return TranslatableMarkup object.
   */
  public function getTitle() {
    return $this->t('Configure plugin @label form', ['@label' => $this->pluginInstance->label()]);
  }

  protected function prepareForm(&$form) {

    $form['settings_group'] = ['#type' => 'vertical_tabs'];

    $form['settings'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Settings'),
      '#group' => 'settings_group',
    ];

    $form['settings']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->pluginInstance->isEnabled(),
    ];

    $form['settings']['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#options' => [
        AdvAuditCheckInterface::SEVERITY_CRITICAL => 'Critical',
        AdvAuditCheckInterface::SEVERITY_HIGH => 'High',
        AdvAuditCheckInterface::SEVERITY_LOW => 'Low',
      ],
      '#default_value' => $this->pluginInstance->getSeverityLevel(),
    ];

    $form['messages'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Messages'),
      '#group' => 'settings_group',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->prepareForm($form);

    if ($this->pluginInstance instanceof PluginFormInterface) {
      // Apply subform functionality.
      $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
      $form['settings'] = $this->pluginInstance->buildConfigurationForm($form['settings'], $subform_state);
    }

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_DESCRIPTION] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $this->messageStorage->get($this->pluginId, AuditMessagesStorageInterface::MSG_TYPE_DESCRIPTION),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_ACTIONS] = [
      '#type' => 'text_format',
      '#title' => $this->t('Action'),
      '#description' => $this->t('What actions should be provided to fix plugin issue.'),
      '#default_value' => $this->messageStorage->get($this->pluginId, AuditMessagesStorageInterface::MSG_TYPE_ACTIONS),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_IMPACTS] = [
      '#type' => 'text_format',
      '#title' => $this->t('Impact'),
      '#description' => $this->t('Why this issue should be fixed.'),
      '#default_value' => $this->messageStorage->get($this->pluginId, AuditMessagesStorageInterface::MSG_TYPE_IMPACTS),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_FAIL] = [
      '#type' => 'text_format',
      '#title' => $this->t('Fail message'),
      '#description' => $this->t('This text is used in case when verification was failed.'),
      '#default_value' => $this->messageStorage->get($this->pluginId, AuditMessagesStorageInterface::MSG_TYPE_FAIL),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_SUCCESS] = [
      '#type' => 'text_format',
      '#title' => $this->t('Success message'),
      '#description' => $this->t('This text is used in case when verification was failed.'),
      '#default_value' => $this->messageStorage->get($this->pluginId, AuditMessagesStorageInterface::MSG_TYPE_SUCCESS),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save plugin configuration'),
    ];

    $form['run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run'),
      '#submit' => ['::runTest'],
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messages = $form_state->getValue('messages');
    foreach ($messages as &$message) {
      $message = $message['value'];
    }
    $this->messageStorage->set($this->pluginId, $messages);

    if ($this->pluginInstance instanceof PluginFormInterface) {
      $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
      $this->pluginInstance->submitConfigurationForm($form['settings'], $subform_state);
    }

    $this->pluginInstance->setPluginStatus($form_state->getValue('status'));
    $this->pluginInstance->setSeverityLevel($form_state->getValue('severity'));

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->pluginInstance instanceof PluginFormInterface) {
      $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
      $this->pluginInstance->validateConfigurationForm($form['settings'], $subform_state);
    }
  }

  /**
   * Checks if the user has access for edit this plugin.
   */
  public function checkAccess(AccountInterface $account) {
    $id = $this->pluginInstance->getCategoryName();
    return AccessResult::allowedIfHasPermission($account, "adv_audit category $id edit");
  }

  /**
   * Temporary submit handler for run audit and display result.
   */
  public function runTest(array &$form, FormStateInterface $form_state) {
    // Set context action for instance initialize plugin.
    $configuration[AuditExecutable::AUDIT_EXECUTE_RUN] = TRUE;
    $messages = new AuditMessageCapture();
    $executable = new AuditExecutable($this->pluginInstance->id(), $configuration, $messages);

    $test_reason = $executable->performTest();
    if ($test_reason->getStatus() == AuditResultResponseInterface::RESULT_PASS) {
      drupal_set_message($this->t('Audit check is PASSED'), 'status');
    }
    else {
      drupal_set_message($this->t('Audit check is FAILED<br/>Reason:<p>@reason</p>', ['@reason' => $test_reason->getReason()]), 'error');
    }

    // Try to build output from plugin instance.
    if ($this->pluginInstance instanceof AdvAuditReasonRenderableInterface) {
      // If needed you can add call to ::auditReportRender for test.
    }

  }

}
