<?php

namespace Drupal\adv_audit\Form;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Batch\AuditRunBatch;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Audit Result entity edit forms.
 *
 * @ingroup adv_audit
 */
class AuditEntityForm extends ContentEntityForm {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs an AuditEntityForm object for use DI.
   */
  public function __construct(AccountProxy $current_user, EntityManagerInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\adv_audit\Entity\AuditEntity */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new version of report'),
        '#default_value' => TRUE,
        '#weight' => 10,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // No other way to pass the entity to batch finished handler.
    $_SESSION['result_entity_id'] = $entity->id();

    // Get previous result of audit.
    $result_audit = $entity->get('audit_results')->first()->getValue();
    if ($result_audit instanceof AuditResultResponseInterface) {
      $test_ids = [];
      foreach ($result_audit->getAuditResults() as $audit_reason) {
        if ($audit_reason instanceof AuditReason) {
          $test_ids[] = $audit_reason->getPluginId();
        }
      }

      // Configure batch.
      $batch = [
        'title' => $this->t('Running process audit'),
        'init_message' => $this->t('Prepare to process.'),
        'progress_message' => $this->t('Progress @current out of @total.'),
        'error_message' => $this->t('An error occurred. Rerun the process or consult the logs.'),
        'operations' => [
          [
            [AuditRunBatch::class, 'run'],
            [$test_ids, []],
          ],
        ],
        'finished' => [
          AuditRunBatch::class, 'finished',
        ],
      ];
      batch_set($batch);
    }
    $form_state->setRedirect('entity.adv_audit.canonical', ['adv_audit' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if (!$this->entity->isNew()) {
      $actions['submit']['#value'] = $this->t('Update report audit');
    }
    return $actions;
  }

}
