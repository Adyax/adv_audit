<?php

namespace Drupal\adv_audit\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\adv_audit\Plugin\AuditPluginsManager;
use Drupal\adv_audit\AuditCategoryManagerService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AdvAuditCategorySettingsForm.
 */
class AdvAuditCategorySettingsForm extends FormBase {

  /**
   * Drupal\adv_audit\Plugin\AuditPluginsManager definition.
   *
   * @var \Drupal\adv_audit\Plugin\AuditPluginsManager
   */
  protected $pluginManagerAdvAuditCheck;
  /**
   * Drupal\adv_audit\AuditCategoryManagerService definition.
   *
   * @var \Drupal\adv_audit\AuditCategoryManagerService
   */
  protected $advAuditManagerCategory;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new AdvAuditCategorySettingsForm object.
   */
  public function __construct(
    AuditPluginsManager $plugin_manager_adv_audit_check,
    AuditCategoryManagerService $adv_audit_manager_category,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack
  ) {
    $this->pluginManagerAdvAuditCheck = $plugin_manager_adv_audit_check;
    $this->advAuditManagerCategory = $adv_audit_manager_category;
    $this->configFactory = $config_factory;
    $this->request = $request_stack->getCurrentRequest();
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.adv_audit_check'),
      $container->get('adv_audit.manager.category'),
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adv_audit_category_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $category_id = $this->request->attributes->get('category_id');
    $category_info = $this->advAuditManagerCategory->getCategoryDefinition($category_id);
    $form['#tree'] = TRUE;

    $form['category_id'] = [
      '#type' => 'hidden',
      '#value' => $category_id,
    ];

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Category settings'),
      'label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $category_info['label'],
      ],
      'status' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $category_info['status'],
      ],
    ];

    $form['plugins'] = [
      '#type' => 'table',
      '#prefix' => $this->t('<h2>Available plugins</h2>'),
      '#header' => [
        $this->t('Plugin label'),
        $this->t('Operations'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
      '#empty' => t('There are currently no plugins in this category.'),
    ];

    foreach ($this->pluginManagerAdvAuditCheck->getPluginsByCategoryFilter($category_id) as $plugin_id => $plugin_definition) {
      /** @var \Drupal\adv_audit\Plugin\AuditBasePlugin $plugin_instance */
      $plugin_instance = $this->pluginManagerAdvAuditCheck->createInstance($plugin_id);
      // Mark the table row as draggable.
      $form['plugins'][$plugin_id]['#attributes']['class'][] = 'draggable';
      $form['plugins'][$plugin_id]['#weight'] = $plugin_instance->getWeight();
      $form['plugins'][$plugin_id]['label'] = [
        '#markup' => $plugin_instance->label()
      ];

      $form['plugins'][$plugin_id]['operations'] = [
        '#type' => 'dropbutton',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('adv_audit.plugin.settings', ['plugin_id' => $plugin_id]),
          ],
        ]
      ];

      $form['plugins'][$plugin_id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this
          ->t('Weight for @title', [
            '@title' => $plugin_instance->label(),
          ]),
        '#title_display' => 'invisible',
        '#default_value' => $plugin_instance->getWeight(),
        // Classify the weight element for #tabledrag.
        '#attributes' => [
          'class' => [
            'table-sort-weight',
          ],
        ],
      ];
    }

    // Sort
    uasort($form['plugins'], ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    // Form action buttons.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'title' => $this
          ->t('Return to Overview page'),
      ],
      '#submit' => [
        '::cancel',
      ],
      '#limit_validation_errors' => [],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Form submission handler for the 'Return to' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state
      ->setRedirect('adv_audit.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $cat_id = $form_state->getValue('category_id');
    $this->advAuditManagerCategory->updateCategoryDefinitionValue($cat_id, 'label', $form_state->getValue(['settings', 'label']));
    $this->advAuditManagerCategory->updateCategoryDefinitionValue($cat_id, 'status', $form_state->getValue(['settings', 'status']));
    foreach ($form_state->getValue('plugins') as $plugin_id => $plugin_data) {
      /** @var \Drupal\adv_audit\Plugin\AuditBasePlugin $plugin_instance */
      $plugin_instance = $this->pluginManagerAdvAuditCheck->createInstance($plugin_id);
      $plugin_instance->setWeight($plugin_data['weight']);
    }

  }

}
