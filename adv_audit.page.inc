<?php

/**
 * @file
 * Contains adv_audit.page.inc.
 *
 * Page callback for Audit Result entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Audit Result entity templates.
 *
 * Default template: adv_audit.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_adv_audit(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
