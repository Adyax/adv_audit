<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Check if agregation for js and css is enabled.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "js_css_agregation",
 *   label = @Translation("Javascript & CSS aggregation"),
 *   description = @Translation("Allows you to improve the frontend performance
 *   of your site."),
 *   category = "performance",
 *   status = TRUE,
 *   severity = "high"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class JsCssAgregation extends AdvAuditCheckpointBase {

  protected $actionMessage = 'Enable core aggregation or use @link (that includes all latest security updates).';

  protected $impactMessage = 'Without aggregation pages are loaded slowly as it’s a lot of css/js files are requires more time for loading..';

  protected $resultDescription = 'When your CSS and JavaScript files are aggregated, there will be a lot less requests to be process down the wire, resulting in a faster page load.';

  /**
   * Return information about next actions.
   *
   * @return mixed
   *   Associated array.
   */
  public function getActions() {
    $link = Link::fromTextAndUrl('Advanced CSS/JS Aggregation', Url::fromUri('https://www.drupal.org/project/advagg'));
    $params = ['@link' => $link->toString()];
    return parent::getActions($params);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return $this->t('Check if agregation for js and css is enabled');
  }

  /**
   * Process checkpoint review.
   */
  public function process() {
    $css_preprocess = $this->configFactory->get('system.performance')
      ->get('css.preprocess');
    $js_preprocess = $this->configFactory->get('system.performance')
      ->get('js.preprocess');

    if (!$css_preprocess || !$js_preprocess) {
      $this->setProcessStatus('fail');
    }
    else {
      $this->setProcessStatus('success');
    }

    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->get('result_description'),
      'information' => $this->getProcessResult(),
      'status' => $this->getProcessStatus(),
      'severity' => $this->get('severity'),
      'actions' => $this->getActions(),
      'impacts' => $this->getImpacts(),
    ];

    \Drupal::logger('test results')
      ->notice('<pre>' . print_r($result, 1) . '</pre>');

    $results[$this->get('category')][$this->getPluginId()] = $result;
    return $results;
  }

  /**
   * Allow to extend settings form.
   *
   * @return array|mixed
   *   Return part of settings form for plugin.
   */
  public function settingsForm() {
    $values = $this->getInformation();
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custon settings field'),
      '#default_value' => isset($values['custom_settings']['test']) ? $values['custom_settings']['test'] : 'Some test value',
    ];
    return $form;
  }

}
