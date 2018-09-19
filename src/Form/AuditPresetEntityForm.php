<?php

namespace Drupal\adv_audit\Form;

use Drupal\adv_audit\Service\AuditCategoryManagerService;
use Drupal\adv_audit\Batch\AuditRunBatch;
use Drupal\adv_audit\Plugin\AuditPluginsManager;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AuditPresetEntityForm.
 */
class AuditPresetEntityForm extends EntityForm {

  /**
   * Container for AuditPluginsManager.
   *
   * @var \Drupal\adv_audit\Plugin\AuditPluginsManager
   */
  protected $advAuditCheckPluginManager;

  /**
   * Container for CategoryManagerService.
   *
   * @var \Drupal\adv_audit\Service\AuditCategoryManagerService
   */
  protected $categoryManagerService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.adv_audit_check'),
      $container->get('adv_audit.manager.category')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AuditPluginsManager $adv_audit_check_manager, AuditCategoryManagerService $category_manager) {
    $this->advAuditCheckPluginManager = $adv_audit_check_manager;
    $this->categoryManagerService = $category_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $adv_audit_preset_entity = $this->entity;
    $plugin_list = $adv_audit_preset_entity->get('plugins');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $adv_audit_preset_entity->label(),
      '#description' => $this->t("Name of preset."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $adv_audit_preset_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\adv_audit\Entity\AuditPresetEntity::load',
      ],
      '#disabled' => !$adv_audit_preset_entity->isNew(),
    ];

    $form['plugins'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available checkpoint plugins'),
      '#description' => $this->t('Select needed checkpoint plugins for run.'),
    ];

    foreach ($this->categoryManagerService->getListOfCategories() as $category_id => $category_label) {
      $form['plugins'][$category_id] = [
        '#type' => 'fieldset',
        '#title' => $category_label,
        '#attributes' => [
          'class' => ['category-wrapper'],
        ],
      ];
      foreach ($this->advAuditCheckPluginManager->getPluginsByCategoryFilter($category_id) as $plugin) {
        $pid = $plugin['id'];
        $plugin_insatnce = $this->advAuditCheckPluginManager->createInstance($pid);
        $is_enabled = $plugin_insatnce->isEnabled();
        $form['plugins'][$category_id][$pid] = [
          '#type' => 'checkbox',
          '#title' => $plugin['label'],
          '#attributes' => [],
          '#default_value' => isset($plugin_list[$pid]) ? $plugin_list[$pid] : 0,
          '#weight' => $plugin_insatnce->getWeight(),
        ];
        if (!$is_enabled) {
          $form['plugins'][$category_id][$pid]['#attributes']['disabled'] = 'disabled';
          $form['plugins'][$category_id][$pid]['#attributes']['title'] = $this->t('This plugin are disabled.');
        }
      }
      uasort($form['plugins'][$category_id], [SortArray::class, 'sortByWeightProperty']);

      if (isset($pid) && empty($form['plugins'][$category_id][$pid])) {
        $form['plugins'][$category_id][$pid]['#markup'] = $this->t('There are no audit plugins available for this category.');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $adv_audit_preset_entity = $this->entity;
    $plugin_list = $form_state->getValues();

    unset($plugin_list['id']);
    unset($plugin_list['label']);
    $adv_audit_preset_entity->set('plugins', $plugin_list);
    $status = $adv_audit_preset_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Adv audit preset entity.', [
          '%label' => $adv_audit_preset_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Adv audit preset entity.', [
          '%label' => $adv_audit_preset_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($adv_audit_preset_entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function actionsElement(array $form, FormStateInterface $form_state) {
    $elements = parent::actionsElement($form, $form_state);
    if (!$this->entity->isNew()) {
      $elements['execute'] = [
        '#type' => 'submit',
        '#value' => $this->t('Execute preset'),
        '#submit' => ['::submitForm', '::save', '::presetRunBatch'],
      ];
    }
    return $elements;
  }

  /**
   * Submit handler for run batch operations.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function presetRunBatch(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Unset not plugin value.
    unset($values['label']);
    unset($values['id']);
    // Configure batch.
    $batch = [
      'title' => $this->t('Running process audit'),
      'init_message' => $this->t('Prepare to process.'),
      'progress_message' => $this->t('Progress @current out of @total.'),
      'error_message' => $this->t('An error occurred. Rerun the process or consult the logs.'),
      'operations' => [
        [
          [AuditRunBatch::class, 'run'],
          [array_keys(array_filter($values)), []],
        ],
      ],
      'finished' => [
        AuditRunBatch::class, 'finished',
      ],
    ];
    batch_set($batch);
  }

}
