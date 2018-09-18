<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\adv_audit\Entity\IssueEntityInterface;

/**
 * Class IssueEntityController.
 *
 *  Returns responses for Audit Issue routes.
 */
class IssueEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Audit Issue  revision.
   *
   * @param int $adv_audit_issue_revision
   *   The Audit Issue  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($adv_audit_issue_revision) {
    $adv_audit_issue = $this->entityManager()->getStorage('adv_audit_issue')->loadRevision($adv_audit_issue_revision);
    $view_builder = $this->entityManager()->getViewBuilder('adv_audit_issue');

    return $view_builder->view($adv_audit_issue);
  }

  /**
   * Page title callback for a Audit Issue  revision.
   *
   * @param int $adv_audit_issue_revision
   *   The Audit Issue  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($adv_audit_issue_revision) {
    $adv_audit_issue = $this->entityManager()->getStorage('adv_audit_issue')->loadRevision($adv_audit_issue_revision);
    return $this->t('Revision of %title from %date', ['%title' => $adv_audit_issue->label(), '%date' => format_date($adv_audit_issue->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Audit Issue .
   *
   * @param \Drupal\adv_audit\Entity\IssueEntityInterface $adv_audit_issue
   *   A Audit Issue  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(IssueEntityInterface $adv_audit_issue) {
    $account = $this->currentUser();
    $langcode = $adv_audit_issue->language()->getId();
    $langname = $adv_audit_issue->language()->getName();
    $languages = $adv_audit_issue->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $adv_audit_issue_storage = $this->entityManager()->getStorage('adv_audit_issue');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $adv_audit_issue->label()]) : $this->t('Revisions for %title', ['%title' => $adv_audit_issue->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = ($account->hasPermission("revert all audit issue revisions") || $account->hasPermission('administer audit issue entities'));
    $delete_permission = ($account->hasPermission("delete all audit issue revisions") || $account->hasPermission('administer audit issue entities'));

    $rows = [];

    $vids = $adv_audit_issue_storage->revisionIds($adv_audit_issue);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\adv_audit\Entity\IssueEntityInterface $revision */
      $revision = $adv_audit_issue_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if (!$revision->hasTranslation($langcode) || !($revision->getTranslation($langcode)->isRevisionTranslationAffected())) {
        // Skip others.
        continue;
      }

      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];

      // Use revision link to link to revisions that are not active.
      $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
      if ($vid != $adv_audit_issue->getRevisionId()) {
        $link = $this->l($date, new Url('entity.adv_audit_issue.revision', ['adv_audit_issue' => $adv_audit_issue->id(), 'adv_audit_issue_revision' => $vid]));
      }
      else {
        $link = $adv_audit_issue->link($date);
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
            'url' => Url::fromRoute('entity.adv_audit_issue.revision_revert', ['adv_audit_issue' => $adv_audit_issue->id(), 'adv_audit_issue_revision' => $vid]),
          ];
        }

        if ($delete_permission) {
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('entity.adv_audit_issue.revision_delete', ['adv_audit_issue' => $adv_audit_issue->id(), 'adv_audit_issue_revision' => $vid]),
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

    $build['adv_audit_issue_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
