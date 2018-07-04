<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\State;
use Drupal\Core\Render\Renderer;

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
  protected $status;

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
   * The render service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  public $noActionMessage = 'No actions needed.';

  public $actionMessage = '';

  public $impactMessage = '';

  public $failMessage = '';

  public $successMessage = '';

  /**
   * AdvAuditCheckpointBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $destination
   *   The redirect destination service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $generator
   *   The link generator service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $handler
   *   The module handler.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_service
   *   The query service.
   * @param \Drupal\Core\State\State $state
   *   The state service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The render service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RedirectDestinationInterface $destination, LinkGeneratorInterface $generator, ConfigFactoryInterface $factory, ModuleHandlerInterface $handler, QueryFactory $query_service, State $state, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->destination = $destination;
    $this->linkGenerator = $generator;
    $this->configFactory = $factory;
    $this->moduleHandler = $handler;
    $this->queryService = $query_service;
    $this->state = $state;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('redirect.destination'),
      $container->get('link_generator'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity.query'),
      $container->get('state'),
      $container->get('renderer')
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
  public function getActions($params) {
    if ($this->getProcessStatus() == $this::FAIL) {
      return $this->t($this->actionMessage, $params);
    }
    return $this->t($this->noActionMessage);
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
    if ($this->getProcessStatus() == 'fail') {
      return $this->t($this->failMessage, $params);
    }
    return $this->t($this->successMessage, $params);
  }

  /**
   * Get plugin information.
   */
  public function getInformation() {
    $key = 'adv_audit.' . $this->getPluginId();
    return $this->state->get($key) ? $this->state->get($key) : $this->getPluginDefinition();
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
   * Return severity list, according to the audit template.
   */
  public function getSeverityOptions() {
    return [
      'low' => $this->t('Low'),
      'high' => $this->t('High'),
      'critical' => $this->t('Critical'),
    ];
  }

}
