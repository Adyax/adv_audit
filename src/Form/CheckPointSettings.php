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

    // Build Storage key.
    $this->storage_key = 'adv_audit.' . $plugin_id;
    $this->defaultValues = ($info = $state->get($this->storage_key)) ? $info : $plugins[$plugin_id];
    $this->plugin = $manager->manager->createInstance($plugin_id);
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
      '#title' => t('Description'),
      '#default_value' => $this->defaultValues['description'],
    ];

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
    $this->defaultValues['severity'] = $values['severity'];
    $this->state->set($this->storage_key, $this->defaultValues);
  }

}
