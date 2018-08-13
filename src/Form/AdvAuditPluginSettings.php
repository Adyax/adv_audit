<?php

namespace Drupal\adv_audit\Form;

use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupla\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides implementation for the Run form.
 */
class AdvAuditPluginSettings extends FormBase {

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
  protected $plugin_id;

  /**
   * The plugin instance.
   *
   * @var \Drupal\adv_audit\Plugin\AdvAuditCheckBase
   */
  protected $pluginInstance;

  /**
   * AdvAuditPluginSettings constructor.
   */
  public function __construct(AdvAuditCheckManager $manager, AuditMessagesStorageInterface $storage_message, RequestStack $request_stack) {
    $this->advAuditPluginManager = $manager;
    $this->messageStorage = $storage_message;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->plugin_id = $request_stack->getCurrentRequest()->attributes->get('plugin_id');
    $this->pluginInstance = $this->advAuditPluginManager->createInstance($this->plugin_id);
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
   */
  public function getTitle() {
    return $this->t('Configure plugin @label form', ['@label' => $this->pluginInstance->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $this->pluginInstance->isEnabled(),
    ];

    $form['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#options' => [
        AdvAuditCheckInterface::SEVERITY_CRITICAL => 'Critical',
        AdvAuditCheckInterface::SEVERITY_HIGH => 'High',
        AdvAuditCheckInterface::SEVERITY_LOW => 'Low',
      ],
      '#default_value' => $this->pluginInstance->getSeverityLevel(),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_DESCRIPTION] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $this->messageStorage->get($this->plugin_id, AuditMessagesStorageInterface::MSG_TYPE_DESCRIPTION),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_ACTIONS] = [
      '#type' => 'text_format',
      '#title' => $this->t('Action'),
      '#description' => $this->t('What actions should be provided to fix plugin issue.'),
      '#default_value' => $this->messageStorage->get($this->plugin_id, AuditMessagesStorageInterface::MSG_TYPE_ACTIONS),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_IMPACTS] = [
      '#type' => 'text_format',
      '#title' => $this->t('Impact'),
      '#description' => $this->t('Why this issue should be fixed.'),
      '#default_value' => $this->messageStorage->get($this->plugin_id, AuditMessagesStorageInterface::MSG_TYPE_IMPACTS),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_FAIL] = [
      '#type' => 'text_format',
      '#title' => $this->t('Fail message'),
      '#description' => $this->t('This text is used in case when verification was failed.'),
      '#default_value' => $this->messageStorage->get($this->plugin_id, AuditMessagesStorageInterface::MSG_TYPE_FAIL),
    ];

    $form['messages'][AuditMessagesStorageInterface::MSG_TYPE_SUCCESS] = [
      '#type' => 'text_format',
      '#title' => $this->t('Success message'),
      '#description' => $this->t('This text is used in case when verification was failed.'),
      '#default_value' => $this->messageStorage->get($this->plugin_id, AuditMessagesStorageInterface::MSG_TYPE_SUCCESS),
    ];

    if ($additional_form = $this->pluginInstance->configForm()) {
      $form['additional_settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Specific plugin settings'),
        '#tree' => TRUE,
        'plugin_config' => $additional_form,
      ];
    }

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
   * Temporary submit handler for run audit and display result.
   */
  public function runTest(array &$form, FormStateInterface $form_state) {
    // Check what plugin requirements are met.
    try {
      $this->pluginInstance->checkRequirements();
    }
    catch (RequirementsException $e) {
      drupal_set_message($e->getMessage(), 'error');
      return;
    }

    // Try run the test and grab the result.
    $result = $this->pluginInstance->perform();
    if ($result->getStatus() == AuditResultResponseInterface::RESULT_PASS) {
      drupal_set_message($this->t('Audit check is PASSED'), 'status');
    }
    else {
      drupal_set_message($this->t('Audit check is FAILED<br/>Reason:<p>@reason</p>', ['@reason' => implode('<br/>', $result->getReason())]), 'error');
    }

    // Try to build output from plugin instance.
    if ($this->pluginInstance instanceof AdvAuditReasonRenderableInterface) {
      // If needed you can add call to ::auditReportRender for test.
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->pluginInstance->setPluginStatus($form_state->getValue('status'));
    $this->pluginInstance->setSeverityLevel($form_state->getValue('severity'));
    foreach ($form_state->getValue('messages', []) as $type => $text) {
      $this->messageStorage->set($this->plugin_id, $type, $text['value']);
    }

    // Handle plugin config form submit.
    $this->pluginInstance->configFormSubmit($form, $form_state);
  }

}
