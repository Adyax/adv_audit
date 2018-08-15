<?php

namespace Drupal\adv_audit\Form;

use Drupal\adv_audit\Batch\AuditRunTestBatch;
use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
      $container->get('plugin.manager.adv_audit_check')
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
    $form['process_list'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available test list:'),
      'list' => ['#markup' => $this->buildProcessItems()],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start audit'),
    ];

    return $form;
  }

  /**
   * Return markup with enabled for audit plugins.
   */
  protected function buildProcessItems() {
    $items = [];
    // Get all available tests.
    $categories = $this->configCategories->get('adv_audit_settings')['categories'];
    foreach ($this->auditTestManager->getPluginsByCategory() as $category_id => $plugins) {
      $items[$category_id]['title'] = $categories[$category_id]['label'];
      $items[$category_id]['items'] = [];
      foreach ($plugins as $plugin_id => $plugin_definition) {
        $items[$category_id]['items'][$plugin_id]['label'] = $plugin_definition['label']->__toString();
        $items[$category_id]['items'][$plugin_id]['id'] = $plugin_definition['id'];
      }
    }
    $render_array = [
      '#theme' => 'adv_audit_run_process',
      '#categories' => $items,
    ];
    return render($render_array);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tests = $this->auditTestManager->getDefinitions();
    $batch = [
      'title' => $this->t('Running process audit'),
      'init_message' => $this->t('Prepare to process.'),
      'progress_message' => $this->t('Progress @current out of @total.'),
      'error_message' => $this->t('An error occurred. Rerun the process or consult the logs.'),
      'operations' => [
        [
          [AuditRunTestBatch::class, 'run'],
          [array_keys($tests), []],
        ],
      ],
      'finished' => [
        AuditRunTestBatch::class, 'finished',
      ],
    ];
    batch_set($batch);
  }

}
