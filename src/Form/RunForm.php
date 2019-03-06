<?php

namespace Drupal\adv_audit\Form;

use Drupal\adv_audit\Batch\AuditRunBatch;
use Drupal\adv_audit\Plugin\AuditPluginsManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides implementation for the Run form.
 */
class RunForm extends FormBase {

  /**
   * The adv_audit.checklist service.
   *
   * @var \Drupal\adv_audit\Plugin\AuditPluginsManager
   */
  protected $auditTestManager = [];

  protected $configCategories;

  protected $renderer;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * RunForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Use DI to work with congig.
   * @param \Drupal\adv_audit\Plugin\AuditPluginsManager $manager
   *   Use DI to work with services.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Get messenger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AuditPluginsManager $manager, MessengerInterface $messenger) {
    $this->configCategories = $config_factory->get('adv_audit.settings');
    $this->auditTestManager = $manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.adv_audit_check'),
      $container->get('messenger')
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
      '#title' => $this->t('Enabled plugins:'),
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
    $categories = $this->configCategories->get('categories');
    foreach ($this->auditTestManager->getPluginsByCategory() as $category_id => $plugins) {
      if (empty($categories[$category_id]['status'])) {
        // Skip disabled categories.
        continue;
      }

      $items[$category_id]['title'] = $categories[$category_id]['label'];
      $items[$category_id]['items'] = [];
      foreach ($plugins as $plugin_id => $plugin_definition) {
        if (!$this->isPluginEnabled($plugin_id)) {
          continue;
        }

        $items[$category_id]['items'][$plugin_id]['label'] = $plugin_definition['label'];
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $categories = $this->configCategories->get('categories');
    // Run AuditChecks implemented via plugins.
    $all_audit_plugins = $this->auditTestManager->getDefinitions();
    $audit_plugins_to_run = [];

    foreach ($all_audit_plugins as $plugin_id => $plugin) {
      $category = $plugin['category'];
      if (empty($categories[$category]['status'])) {
        // Skip plugins from disabled categories.
        continue;
      }

      if (!$this->isPluginEnabled($plugin_id)) {
        continue;
      }

      // Add the plugin to Batch jobs.
      $audit_plugins_to_run[$plugin_id] = $plugin;
    }

    if (!empty($audit_plugins_to_run)) {
      $form_state->setValue('plugins_to_run', $audit_plugins_to_run);
    }
    else {
      $this->messenger->addMessage($this->t("No one plugin is enabled to check!"), 'error');
      $redirect = new RedirectResponse(Url::fromRoute('adv_audit.run')
        ->setAbsolute()
        ->toString());
      $redirect->send();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $batch = [
      'title' => $this->t('Running process audit'),
      'init_message' => $this->t('Prepare to process.'),
      'progress_message' => $this->t('Progress @current out of @total.'),
      'error_message' => $this->t('An error occurred. Rerun the process or consult the logs.'),
      'operations' => [
        [
          [AuditRunBatch::class, 'run'],
          [array_keys($form_state->getValue('plugins_to_run')), []],
        ],
      ],
      'finished' => [
        AuditRunBatch::class,
        'finished',
      ],
    ];
    batch_set($batch);
  }

  /**
   * Check if the Audit plugin is enabled.
   *
   * @param string $plugin_id
   *   Audit plugin.
   *
   * @return bool
   *   TRUE if the plugin is enabled.
   */
  protected function isPluginEnabled($plugin_id) {
    try {
      $plugin_instance = $this->auditTestManager->createInstance($plugin_id);
      // Skip disabled plugins from audit.
      if (!$plugin_instance->getStatus()) {
        return FALSE;
      }
    } catch (\Exception $e) {
      // Nothing to do here.
      // Broken plugins are still considered to be enabled.
    }

    return TRUE;
  }

}
