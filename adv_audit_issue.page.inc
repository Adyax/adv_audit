<?php

/**
 * @file
 * Contains adv_audit_issue.page.inc.
 *
 * Page callback for Audit Issue entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Audit Issue templates.
 *
 * Default template: adv_audit_issue.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_adv_audit_issue(array &$variables) {
  // Fetch IssueEntity Entity Object.
  // $adv_audit_issue = $variables['elements']['#adv_audit_issue'];
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
