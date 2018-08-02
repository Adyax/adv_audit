<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\adv_audit\Entity\AdvAuditEntityInterface;

/**
 * Class AdvAuditEntityController.
 *
 *  Returns responses for Audit Result entity routes.
 */
class AdvAuditEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Audit Result entity  revision.
   *
   * @param int $adv_audit_revision
   *   The Audit Result entity  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($adv_audit_revision) {
    $adv_audit = $this->entityManager()->getStorage('adv_audit')->loadRevision($adv_audit_revision);
    $view_builder = $this->entityManager()->getViewBuilder('adv_audit');

    return $view_builder->view($adv_audit);
  }

  /**
   * Page title callback for a Audit Result entity  revision.
   *
   * @param int $adv_audit_revision
   *   The Audit Result entity  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($adv_audit_revision) {
    $adv_audit = $this->entityManager()->getStorage('adv_audit')->loadRevision($adv_audit_revision);
    return $this->t('Revision of %title from %date', ['%title' => $adv_audit->label(), '%date' => format_date($adv_audit->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Audit Result entity .
   *
   * @param \Drupal\adv_audit\Entity\AdvAuditEntityInterface $adv_audit
   *   A Audit Result entity  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(AdvAuditEntityInterface $adv_audit) {
    $account = $this->currentUser();
    $langcode = $adv_audit->language()->getId();
    $langname = $adv_audit->language()->getName();
    $languages = $adv_audit->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $adv_audit_storage = $this->entityManager()->getStorage('adv_audit');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $adv_audit->label()]) : $this->t('Revisions for %title', ['%title' => $adv_audit->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all audit result entity revisions") || $account->hasPermission('administer audit result entity entities')));
    $delete_permission = (($account->hasPermission("delete all audit result entity revisions") || $account->hasPermission('administer audit result entity entities')));

    $rows = [];

    $vids = $adv_audit_storage->revisionIds($adv_audit);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\adv_audit\AdvAuditEntityInterface $revision */
      $revision = $adv_audit_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $adv_audit->getRevisionId()) {
          $link = $this->l($date, new Url('entity.adv_audit.revision', ['adv_audit' => $adv_audit->id(), 'adv_audit_revision' => $vid]));
        }
        else {
          $link = $adv_audit->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.adv_audit.revision_revert', ['adv_audit' => $adv_audit->id(), 'adv_audit_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.adv_audit.revision_delete', ['adv_audit' => $adv_audit->id(), 'adv_audit_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['adv_audit_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
