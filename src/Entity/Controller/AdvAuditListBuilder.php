<?php

namespace Drupal\adv_audit\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a list controller for os_adyax_test_content_entity entity.
 *
 * @ingroup os_adyax_test
 */
class AdvAuditListBuilder extends EntityListBuilder {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('url_generator')
    );
  }

  /**
   * Constructs a new AdvAuditListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Audit Result implements a content-type entity. These entities are fieldable entities. You can manage the fields on the <a href="@adminlink">Audit Results admin page</a>.', [
        '@adminlink' => $this->urlGenerator->generateFromRoute('adv_audit.settings'),
      ]),
    ];
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Entity ID');
    $header['created'] = $this->t('Created');
    $header['title'] = $this->t('Title');
    $header['audit_pdf'] = $this->t('Audit in pdf format');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\adv_audit\Entity\AdvAudit */
    $row['id'] = $entity->id();
    $created = $entity->get('created')->getValue();
    $row['created'] = \Drupal::service('date.formatter')
      ->format($created[0]['value'], 'date_text');
    $url = Url::fromRoute('entity.adv_audit.canonical', ['adv_audit' => $entity->id()]);
    $link = Link::fromTextAndUrl($entity->title->value, $url);
    $row['title'] = $link;

    if ($entity->hasField('pdf') && !$entity->pdf->isEmpty()) {
      $file = $entity->get('pdf')->getValue();
      $fid = $file[0]['target_id'];
      $row['audit_pdf'] = Link::fromTextAndUrl(t('Download'), URL::fromRoute('adv_audit.file_download', ['fid' => $fid]));
    }
    else {
      $row['audit_pdf'] = 'File not yet generated';
    }

    return $row + parent::buildRow($entity);
  }

}
