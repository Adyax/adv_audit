<?php

namespace Drupal\adv_audit\Form;

use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;

/**
 * Provides implementation for the Run form.
 */
class RunForm extends FormBase {

  /**
   * The adv_audit.checklist service.
   */
  protected $auditTestManager = [];

  protected $configCategories;

  protected $renderer;

  /**
   * RunForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Use DI to work with congig.
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckListManager $manager
   *   Use DI to work with services.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Use DI to render.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdvAuditCheckManager $manager) {
    $this->configCategories = $config_factory->get('adv_audit.config');
    $this->auditTestManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.plugin.manager.adv_audit_check')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal-audit-run';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start audit'),
    ];

    $form['project_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project name'),
      '#description' => $this->t('Please enter project name that you are going to audit.'),
      '#required' => TRUE,
    ];

    $form['process_list'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Process list:'),
      'list' => ['#markup' => $this->buildProcessItems()],
    ];

    return $form;
  }

  /**
   * Return markup with enabled for audit plugins.
   */
  protected function buildProcessItems() {
    $items = [];
    $categories = $this->configCategories->get('adv_audit_settings')['categories'];
    foreach ($this->checkPlugins as $key => $category) {
      $items[$key]['title'] = $categories[$key]['label'];
      $items[$key]['items'] = [];
      foreach ($category as $plugin) {
        $items[$key]['items'][$plugin['id']] = [
          'label' => $plugin['label'],
          'id' => $plugin['id'],
        ];
      }
    }
    $render_array = [
      '#theme' => 'adv_audit_run_process',
      '#categories' => $items,
    ];
    return $this->render->render($render_array);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'operations' => [],
      'finished' => '_adv_audit_batch_run_finished',
      'title' => $this->t('Process audit.'),
      'init_message' => $this->t('Prepare to process.'),
      'progress_message' => $this->t('Progress @current out of @total.'),
      'error_message' => $this->t('An error occurred. Rerun the process or consult the logs.'),
      'batch_redirect' => '/adv_audit/5',
    ];

    foreach ($this->checkPlugins as $plugins) {
      foreach ($plugins as $plugin) {
        $plugin = $this->check->manager->createInstance($plugin['id']);
        $batch['operations'][] = [
          '_adv_audit_batch_run_op',
          [$plugin],
        ];
      }
    }
    $batch['operations'][] = [
      '_adv_audit_batch_run_op_last',
      [$form_state->getValue('project_name')],
    ];
    batch_set($batch);
  }

}
