<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Buzz\Client\Curl;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Sonar\SonarClient;
use Drupal\adv_audit\Sonar\SonarHttpClient;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Blocktrail\CryptoJSAES\CryptoJSAES;


/**
 * Check Database usage.
 *
 * @AdvAuditCheck(
 *  id = "sonar_integration",
 *  label = @Translation("Auditing code smells, code complexity. Code metrics
 *   and potential problems"),
 *  category = "code_review",
 *   severity = "normal",
 *   requirements = {}, enabled = true,
 * )
 */
class CodeReviewSonarIntegration extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

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
    $settings = $this->getPerformSettings();
    $this->sonar = new SonarClient($settings['entry_point'], $settings['login'], $settings['password']);
//    $client
    $httpClient = new SonarHttpClient($this->baseUrl, $this->$this->sonar, );
    $this->init();
    if ($this->logged['valid']) {
//      $measures = $this->sonar->api('dashboard');
      $i = 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {

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

  protected function init() {
    $settings = $this->getPerformSettings();
    $settings['password'] = CryptoJSAES::decrypt($settings['password'], Settings::getHashSalt());
    $settings['entry_point'] = trim($settings['entry_point'], '/') . '/api/';
//    $this->logged = $this->sonar->api('authentication')->validate();
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
        $options[$project['id']] = $project['nm'];
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
    $this->state->set($this->buildStateConfigKey(), $value);
  }

  /**
   * Get settings for perform task.
   */
  protected function getPerformSettings() {
    $settings = $this->state->get($this->buildStateConfigKey());
    return !is_null($settings) ? $settings : $this->getDefaultPerformSettings();
  }

  /**
   * Get default settings.
   */
  protected function getDefaultPerformSettings() {
    return [
      'max_table_size' => 512,
      'excluded_tables' => '',
    ];
  }

  /**
   * @inheritdoc
   */
  public function configFormValidate(array $form, FormStateInterface $form_state) {
    $settings = $this->getPerformSettings();
    $value = $form_state->getValue('additional_settings')['plugin_config'];
    if (empty($value['password'])) {
      $value['password'] = $settings['password'];
    }
    else {
      $value['password'] = CryptoJSAES::encrypt($value['password'], Settings::getHashSalt());
    }
//    $this->login($value);
//    if (!$this->logged['valid']) {
//      $form_state->setError($form, 'Wrong connect data');
//    }
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

}
