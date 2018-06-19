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

/**
 * Base class for all the adv_audit check plugins.
 */
abstract class AdvAuditCheckpointBase extends PluginBase implements AdvAuditCheckpointInterface, ContainerFactoryPluginInterface {

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RedirectDestinationInterface $destination, LinkGeneratorInterface $generator, ConfigFactoryInterface $factory, ModuleHandlerInterface $handler, QueryFactory $query_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->destination = $destination;
    $this->linkGenerator = $generator;
    $this->configFactory = $factory;
    $this->moduleHandler = $handler;
    $this->queryService = $query_service;
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
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $definition = $this->getPluginDefinition();
    return $definition['label'];
  }

  /**
   * Return information about plugin according annotation.
   *
   * @return mixed
   *   Associated array.
   */
  public function getCategory() {
    $definition = $this->getPluginDefinition();
    return $definition['category'];
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

}
