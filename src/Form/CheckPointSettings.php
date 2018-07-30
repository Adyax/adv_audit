<?php

namespace Drupal\adv_audit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckListManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\State\State;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides implementation for the Run form.
 */
class CheckPointSettings extends FormBase {

  protected $defaultValues;

  /**
   * RunForm constructor.
   */
  public function __construct(CurrentRouteMatch $routeMatch, AdvAuditCheckListManager $manager, State $state) {

    $this->state = $state;

    // Get plugin id from URL.
    $plugin_id = $routeMatch->getParameters()->get('plugin_id');
    $plugins = $manager->manager->getDefinitions();
    if (!$plugin_id || !isset($plugins[$plugin_id])) {
      $this->defaultValues = FALSE;
      return;
    }
    $this->plugin = $manager->manager->createInstance($plugin_id);
    $this->defaultValues = $this->plugin->getInformation();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('plugin.manager.adv_audit_checklist'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced-audit-edit-plugin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (!$this->defaultValues) {
      throw new NotFoundHttpException();
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $this->defaultValues['status'],
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->defaultValues['label'],
    ];

    $form['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#default_value' => $this->defaultValues['severity'],
      '#options' => $this->plugin->getSeverityOptions(),
    ];

    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $this->defaultValues['description'],
    ];

    $form['result_description'] = [
      '#type' => 'text_format',
      '#title' => t('Description for result output'),
      '#default_value' => $this->defaultValues['result_description'],
    ];

    $form['action_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Action'),
      '#description' => $this->t('What actions should be provided to fix plugin issue.'),
      '#default_value' => $this->defaultValues['action_message'],
    ];

    $form['impact_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Impact'),
      '#description' => $this->t('Why this issue should be fixed.'),
      '#default_value' => $this->defaultValues['impact_message'],
    ];

    $form['fail_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Fail message'),
      '#description' => $this->t('This text is used in case when verification was failed.'),
      '#default_value' => $this->defaultValues['fail_message'],
    ];

    $form['success_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Success message'),
      '#description' => $this->t('This text is used in case when verification was failed.'),
      '#default_value' => $this->defaultValues['success_message'],
    ];

    if ($additional_form = $this->plugin->settingsForm($form, $form_state)) {
      $form['additional_settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Specific plugin settings'),
        '#tree' => TRUE,
        'custom_settings' => $additional_form,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->defaultValues['label'] = $values['label'];
    $this->defaultValues['status'] = $values['status'];
    $this->defaultValues['description'] = $values['description']['value'];
    $this->defaultValues['result_description'] = $values['result_description']['value'];
    $this->defaultValues['action_message'] = $values['action_message']['value'];
    $this->defaultValues['impact_message'] = $values['impact_message']['value'];
    $this->defaultValues['fail_message'] = $values['fail_message']['value'];
    $this->defaultValues['success_message'] = $values['success_message']['value'];
    $this->defaultValues['severity'] = $values['severity'];
    if (isset($values['additional_settings'])) {
      $this->defaultValues['custom_settings'] = $values['additional_settings']['custom_settings'];
    }
    $this->plugin->setInformation($this->defaultValues);
  }

}
