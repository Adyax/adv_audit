<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all the adv_audit check plugins.
 */
abstract class AdvAuditCheckpointBase extends PluginBase implements AdvAuditCheckpointInterface, ContainerFactoryPluginInterface {

  /**
   * Failed verification status.
   */
  const FAIL = 'fail';

  /**
   * Verification Status.
   *
   * @var status
   */
  protected $status = 'success';

  /**
   * The prod check processor plugin manager.
   *
   * @var \Drupal\prod_check\Plugin\ProdCheckProcessorInterface
   */
  protected $processor;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $destination;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The query Service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryService;

  /**
   * The state Service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Provide possibility add additional services to plugin.
   *
   * @var array
   *   Additional services.
   */
  protected $additionalServices = [];

  /**
   * Provide a list of default services.
   */
  const GENERAL_SERVICES = [
    'destination' => 'redirect.destination',
    'linkGenerator' => 'link_generator',
    'configFactory' => 'config.factory',
    'moduleHandler' => 'module_handler',
    'queryService' => 'entity.query',
    'state' => 'state',
    'renderer' => 'renderer',
  ];

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  protected $noActionMessage = 'No actions needed.';

  protected $actionMessage = '';

  protected $impactMessage = '';

  protected $failMessage = 'Error';

  protected $successMessage = 'Ok';

  protected $resultDescription = '';

  /**
   * AdvAuditCheckpointBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The redirect destination service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Add general services.
    foreach ($this::GENERAL_SERVICES as $varname => $service) {
      $this->{$varname} = $container->get($service);
    }

    // Add additional services.
    foreach ($this->additionalServices as $varname => $service) {
      $this->{$varname} = $container->get($service);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container
    );
  }

  /**
   * Return message how can this option impact on website.
   *
   * @param mixed $params
   *   Params for string replacement.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Return message about risks.
   */
  public function getImpacts($params = []) {
    // @codingStandardsIgnoreLine
    return $this->t($this->impactMessage, $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $definition = $this->getPluginDefinition();
    return $definition['label'];
  }

  /**
   * Return information about next actions.
   *
   * @return mixed
   *   Return solution to fix problem.
   */
  public function getActions($params = []) {
    if ($this->getProcessStatus() == $this::FAIL) {
      // @codingStandardsIgnoreLine
      return $this->t($this->actionMessage, $params);
    }
    // @codingStandardsIgnoreLine
    return $this->get('no_action_message') ? $this->t($this->get('no_action_message'), $params) : '';
  }

  /**
   * Get plugin config by key.
   *
   * @param mixed $key
   *   Config key.
   *
   * @return mixed
   *   Return plugin property, or false if property doesn't exist.
   */
  public function get($key) {
    $values = $this->getInformation();
    return isset($values[$key]) ? $values[$key] : FALSE;
  }

  /**
   * Provide plugin process result.
   *
   * @param mixed $params
   *   Params for string replacements.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Return fail or success mesage.
   */
  public function getProcessResult($params = []) {
    if ($this->getProcessStatus() == $this::FAIL) {
      // @codingStandardsIgnoreLine
      return $this->get('fail_message') ? $this->t($this->get('fail_message'), $params) : '';
    }
    // @codingStandardsIgnoreLine
    return $this->get('success_message') ? $this->t($this->get('success_message'), $params) : '';
  }

  /**
   * Get plugin information.
   *
   * @return array|mixed|null
   *   Return plugin information.
   */
  public function getInformation() {
    $key = 'adv_audit.' . $this->getPluginId();
    $values = $this->state->get($key);
    if (!$values) {
      $values = $this->getPluginDefinition();
      $values['result_description'] = $this->resultDescription;
      $values['no_action_message'] = $this->noActionMessage;
      $values['action_message'] = $this->actionMessage;
      $values['impact_message'] = $this->impactMessage;
      $values['fail_message'] = $this->failMessage;
      $values['success_message'] = $this->successMessage;
      $this->state->set($key, $values);
    }
    if ($values['status'] && !$this->validation()) {
      $values['status'] = FALSE;
      $this->state->set($key, $values);
    }
    return $values;
  }

  /**
   * Allow change plugin information.
   *
   * @param mixed $data
   *   Array with new property values for plugin.
   */
  public function setInformation($data) {
    if ($this->validation()) {
      $key = 'adv_audit.' . $this->getPluginId();
      $this->state->set($key, $data);
    }
  }

  /**
   * Return information about plugin category.
   *
   * @return mixed
   *   Associated array.
   */
  public function getCategory() {
    $categories = $this->configFactory->get('adv_audit.config')
      ->get('adv_audit_settings')['categories'];
    // @codingStandardsIgnoreLine
    return isset($categories[$this->get('category')]) ? $categories[$this->get('category')] : FALSE;
  }

  /**
   * Return category title.
   *
   * @return mixed
   *   Associated array.
   */
  public function getCategoryTitle() {
    $category = $this->getCategory();
    return $category ? $category['label'] : FALSE;
  }

  /**
   * Return category status.
   *
   * @return mixed
   *   Associated array.
   */
  public function getCategoryStatus() {
    $category = $this->getCategory();
    return $category ? $category['status'] : FALSE;
  }

  /**
   * Return string with check status.
   *
   * @return string
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function getProcessStatus() {
    return $this->status;
  }

  /**
   * Set check status.
   *
   * @param string $status
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function setProcessStatus($status) {
    $this->status = $status;
  }

  /**
   * Return description for plugin result.
   *
   * @param mixed $params
   *   Placeholders for message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Return plugin description.
   */
  public function getDescription($params = []) {
    // @codingStandardsIgnoreLine
    return $this->get('result_description') ? $this->t($this->get('result_description'), $params) : '';
  }

  /**
   * Return severity list, according to the audit template.
   */
  public function getSeverityOptions() {
    return [
      'low' => $this->t('Low'),
      'high' => $this->t('High'),
      'critical' => $this->t('Critical'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface &$form_state) {
    $form['#validate'][] = [$this, 'settingsFormValidation'];
    return [];
  }

  /**
   * Provide custom validation for plugin.
   *
   * @return mixed
   *   Validation result.
   */
  protected function validation() {
    $is_validated = TRUE;
    $requires = $this->getRequirements();
    if (!empty($requires) && isset($requires['modules'])) {
      foreach ($requires['modules'] as $dependency) {
        if (!$this->moduleHandler->moduleExists($dependency)) {
          $is_validated = FALSE;
          drupal_set_message($this->t('You should install @modulename to use this feature.', ['@modulename' => $dependency]), 'error');
        }
      }
    }
    return $is_validated;
  }

  /**
   * Check plugin requires.
   *
   * @param array $form
   *   Altered form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function settingsFormValidation(array $form, FormStateInterface &$form_state) {
    if (!$this->validation()) {
      $form_state->setError($form, $this->t('Impossible to save changes.'));
    }
  }

  /**
   * Allow add requires for plugin.
   *
   * @return mixed
   *   Plugin requires.
   */
  protected function getRequirements() {
    return [];
  }

}
