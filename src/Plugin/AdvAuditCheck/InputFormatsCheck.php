<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;

use Drupal\Core\Session\AccountInterface;

/**
 * Trusted Host Check plugin class.
 *
 * @AdvAuditCheck(
 *   id = "input_formats_check",
 *   label = @Translation("Dangerous Tags"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class InputFormatsCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $params = [];
    $reason = NULL;
    $status = AuditResultResponseInterface::RESULT_PASS;
    $results = [];
    $formats = filter_formats();
    $untrusted_roles = [AccountInterface::ANONYMOUS_ROLE];
    $unsafe_tags = [
      'applet',
      'area',
      'audio',
      'base',
      'basefont',
      'body',
      'button',
      'comment',
      'embed',
      'eval',
      'form',
      'frame',
      'frameset',
      'head',
      'html',
      'iframe',
      'image',
      'img',
      'input',
      'isindex',
      'label',
      'link',
      'map',
      'math',
      'meta',
      'noframes',
      'noscript',
      'object',
      'optgroup',
      'option',
      'param',
      'script',
      'select',
      'style',
      'svg',
      'table',
      'td',
      'textarea',
      'title',
      'video',
      'vmlframe',
    ];

    foreach ($formats as $format) {
      $format_roles = array_keys(filter_get_roles_by_format($format));
      $intersect = array_intersect($format_roles, $untrusted_roles);

      if (!empty($intersect)) {
        // Untrusted users can use this format.
        // Check format for enabled HTML filter.
        $filter_html_enabled = FALSE;
        if ($format->filters()->has('filter_html')) {
          $filter_html_enabled = $format->filters('filter_html')
            ->getConfiguration()['status'];
        }
        $filter_html_escape_enabled = FALSE;
        if ($format->filters()->has('filter_html_escape')) {
          $filter_html_escape_enabled = $format->filters('filter_html_escape')
            ->getConfiguration()['status'];
        }

        if ($filter_html_enabled) {
          $filter = $format->filters('filter_html');

          // Check for unsafe tags in allowed tags.
          $allowed_tags = array_keys($filter->getHTMLRestrictions()['allowed']);
          foreach (array_intersect($allowed_tags, $unsafe_tags) as $tag) {
            // Found an unsafe tag.
            $results['tags'][$format->id()] = $tag;
          }
        }
        elseif (!$filter_html_escape_enabled) {
          // Format is usable by untrusted users but does not contain the HTML
          // Filter or the HTML escape.
          $results['formats'][$format->id()] = $format->label();
        }
      }
    }

    if (!empty($results)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $params = ['results' => $results];
    }

    return new AuditReason($this->id(), $status, $reason, $params);
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      $arguments = $reason->getArguments();
      if (!empty($arguments['results'])) {
        if (!empty($arguments['results']['tags'])) {
          $build['tags'] = [
            '#theme' => 'item_list',
            '#title' => $this->t('It is recommended you remove the following tags from roles accessible by untrusted users.:'),
            '#list_type' => 'ul',
            '#items' => $arguments['results']['tags'],
          ];
        }
        if (!empty($arguments['results']['formats'])) {
          $build['formats'] = [
            '#theme' => 'item_list',
            '#title' => $this->t('The following formats are usable by untrusted roles and do not filter or escape allowed HTML tags:'),
            '#list_type' => 'ul',
            '#items' => $arguments['results']['formats'],
          ];
        }
        return $build;
      }
    }

    return [];
  }

}
