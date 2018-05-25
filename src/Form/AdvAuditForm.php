<?php

namespace Drupal\adv_audit\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the os_adyax_test_content_entity entity edit forms.
 *
 * @ingroup content_entity_example
 */
class AdvAuditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\adv_audit\Entity\AdvAudit */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.adv_audit.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

}
