<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\adv_audit\Sonar\SonarClient;
use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Blocktrail\CryptoJSAES\CryptoJSAES;


/**
 * Code review. Integration with sonar..
 *
 * @AuditPlugin(
 *  id = "sonar_integration",
 *  label = @Translation("Auditing code smells, code complexity. Code metrics
and potential problems"),
 *  category = "code_review",
 *  requirements = {},
 * )
 */
class CodeReviewSonarIntegration extends AuditBasePlugin implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

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
   * Create issues from sonar data.
   */
  protected function parseIssues() {
    $response = $this->sonar->api('dashboard');
    if ($response->getStatusCode() !== 200) {
      return [];
    }
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
    $settings = $this->getSettings();

    if (empty($settings['password'])) {
      $this->logged['valid'] = FALSE;
      return;
    }

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
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
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $settings = $this->getSettings();
    if (!$values['password']) {
      $values['password'] = $settings['password'];
    }
    elseif ($values['password'] != $settings['password']) {
      $values['password'] = CryptoJSAES::encrypt($values['password'], Settings::getHashSalt());
    }
    $values['entry_point'] = trim($values['entry_point'], '/') . '/';
    $this->pluginSettingsStorage->set(NULL, $values);

  }

  /**
   * @inheritdoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $values = $form_state->getValues();
    if (empty($values['password']) && !empty($settings['password'])) {
      $values['password'] = CryptoJSAES::decrypt($settings['password'], Settings::getHashSalt());
    }

    if (empty($values['password'])) {
      $form_state->setErrorByName('password', $this->t('Password is required field.'));
      return;
    }

    if (!$this->sonar) {
      $this->sonar = new SonarClient($values['entry_point'], $values['login'], $values['password']);
    }

    $base_url_validate = $this->sonar->validateRequest($values['entry_point'], $values);
    if ($base_url_validate->getStatusCode() !== 200) {
      $data = $base_url_validate->getContent();
      if (!is_null(json_decode($data))) {
        $data = json_decode($data);
        foreach ($data->errors as $error) {
          $form_state->setErrorByName('entry_point', $error->msg);
        }
      }
      else {
        $form_state->setErrorByName('password', !empty($data) ? $data : $base_url_validate->getReasonPhrase());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    if ($this->logged['valid'] && !is_null($this->sonar->getProject())) {
      $issues = $this->parseIssues();
      if (empty($issues)) {
        return $this->success();
      }
      else {
        return $this->fail(NULL, ['issues' => $issues]);
      }
    }

  }

}
