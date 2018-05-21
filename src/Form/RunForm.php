<?php

namespace Drupal\adv_audit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides implementation for the Run form.
 */
class RunForm extends FormBase {

  /**
   * The adv_audit.checklist service.
   *
   * @var \Drupal\adv_audit\Checklist
   */
  protected $checklist;

  /**
   * Constructs a RunForm.
   *
   * @param \Drupal\adv_audit\Checklist $checklist
   *   The adv_audit.checklist service.
   */
  public function __construct(Checklist $checklist) {
    $this->checklist = $checklist;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('drupal_audit.checklist')
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //TODO: implement batch
  }

}
