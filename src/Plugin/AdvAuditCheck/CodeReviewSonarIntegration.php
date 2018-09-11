<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Sonar\SonarClient;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Blocktrail\CryptoJSAES\CryptoJSAES;


/**
 * Code review. Integration with sonar..
 *
 * @AdvAuditCheck(
 *  id = "sonar_integration",
 *  label = @Translation("Auditing code smells, code complexity. Code metrics
and potential problems"),
 *  category = "code_review",
 *  severity = "normal",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class CodeReviewSonarIntegration extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  public $sonar;

  /**
   * Constructs a new PerformanceViewsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param mixed $state
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->init();
  }

  /**
   *
   */
  protected function parseIssues() {
    $issues = FALSE;
    if ($this->logged['valid'] && !is_null($this->sonar->getProject())) {
      $response = $this->sonar->api('dashboard');
      if ($response->getStatusCode() === 200) {
        $issues = [];
        $data = $response->getContent();
        if (isset($data['component']['measures']) && is_array($data['component']['measures'])) {
          foreach ($data['component']['measures'] as $item) {
            if ($item['value'] > 0) {
              $issues[$item['metric']] = [
                '@issue_title' => '"@name" - @value',
                '@name' => str_replace('_', ' ', $item['metric']),
                '@value' => $item['value'],
              ];
            }
          }
        }
      }
    }
    return $issues;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * Authorization to sonar.
   */
  protected function init() {
    $settings = $this->getPerformSettings();
    $settings['password'] = CryptoJSAES::decrypt($settings['password'], Settings::getHashSalt());
    $this->sonar = new SonarClient($settings['entry_point'], $settings['login'], $settings['password']);
    if (isset($settings['project']) && $settings['project']) {
      $this->sonar->setProject($settings['project']);
    }

    // Check if data valid, without it we can get fatal error.
    $base_url_validate = $this->sonar->validateRequest($settings['entry_point'], $settings);
    if ($base_url_validate->getStatusCode() === 200) {
      $this->logged = $this->sonar->api('authentication')->validate();
    }
    else {
      drupal_set_message($this->t('Invalid connect data.'));
    }
  }

  /**
   * @inheritdoc
   */
  public function configForm() {
    $settings = $this->getPerformSettings();
    if ($this->logged['valid']) {
      $projects = $this->sonar->api('projects')->search();
      $options = [];
      foreach ($projects as $project) {
        $options[$project['k']] = $project['nm'];
      }
      $form['project'] = [
        '#type' => 'select',
        '#title' => $this->t('Choise project from sonar'),
        '#options' => $options,
        '#default_value' => $settings['project'],
      ];
    }

    $form['entry_point'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api url'),
      '#default_value' => $settings['entry_point'],
    ];

    $form['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login'),
      '#default_value' => $settings['login'],
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('password'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit(array $form, FormStateInterface $form_state) {
    $value = $form_state->getValue('additional_settings')['plugin_config'];
    $settings = $this->getPerformSettings();
    if (!$value['password']) {
      $value['password'] = $settings['password'];
    }
    elseif ($value['password'] != $settings['password']) {
      $value['password'] = CryptoJSAES::encrypt($value['password'], Settings::getHashSalt());
    }
    $value['entry_point'] = trim($value['entry_point'], '/') . '/';
    $this->state->set($this->buildStateConfigKey(), $value);
  }

  /**
   * Get settings for perform task.
   */
  protected function getPerformSettings() {
    $settings = $this->state->get($this->buildStateConfigKey());
    return !is_null($settings) ? $settings : FALSE;
  }

  /**
   * @inheritdoc
   */
  public function configFormValidate(array $form, FormStateInterface $form_state) {
    $settings = $this->getPerformSettings();
    $value = $form_state->getValue('additional_settings')['plugin_config'];
    if (empty($value['password'])) {
      $value['password'] = CryptoJSAES::decrypt($settings['password'], Settings::getHashSalt());
    }
    $base_url_validate = $this->sonar->validateRequest($value['entry_point'], $value);
    if ($base_url_validate->getStatusCode() !== 200) {
      $data = $base_url_validate->getContent();
      if (!is_null(json_decode($data))) {
        $data = json_decode($data);
        foreach ($data->errors as $error) {
          $form_state->setError($form, $error->msg);
        }
      }
      else {
        $form_state->setError($form, $data);
      }
    }
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.additional-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {

    $issues = $this->parseIssues();
    if (!is_array($issues)) {
      $this->skip('Problems with connect to sonar.');
    }

    if (is_array($issues) && empty($issues)) {
      return $this->success();
    }
    else {
      return $this->fail(NULL, ['issues' => $issues]);
    }

  }

}
