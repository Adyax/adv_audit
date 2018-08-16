<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;

/**
 * Provide imageApi optimize check.
 *
 * @AdvAuditCheck(
 *  id = "imageapi_optimize_check",
 *  label = @Translation("ImageAPI Optimize"),
 *  category = "performance",
 *  severity = "low",
 *  enabled = true,
 *  requirements = {},
 * )
 */
class ImageAPICheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

  /**
   * The audit messages storage service.
   *
   * @var \Drupal\adv_audit\Message\AuditMessagesStorageInterface
   */
  protected $messagesStorage;

  /**
   * Interface for working with drupal module system.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ExampleAuditCheckPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\adv_audit\Message\AuditMessagesStorageInterface $messages_storage
   *   Interface for the audit messages.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Interface for working with drupal module system.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AuditMessagesStorageInterface $messages_storage, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messagesStorage = $messages_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('adv_audit.messages'),
      $container->get('module_handler')
    );
  }

  /**
   * The actual procedure of carrying out the check.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   Return AuditReason object instance.
   */
  public function perform() {
    // Created placeholder link for messages.
    $url = Url::fromUri('https://www.drupal.org/project/imageapi_optimize', ['attributes' => ['target' => '_blank']]);
    $link = Link::fromTextAndUrl('ImageAPI Optimize', $url);
    if (!$this->moduleHandler->moduleExists('imageapi_optimize')) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, [$this->t('ImageApi optimize check failed.')], ['%link' => $link->toString()]);
    }

    // Check if pipelines were created.
    $pipelines = imageapi_optimize_pipeline_options(FALSE, TRUE);
    $pipeline_keys = array_keys($pipelines);
    if (count($pipeline_keys) === 1 && empty($pipeline_keys[0])) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, [$this->t('ImageApi is installed, but any pipeline has not been created.')], ['%link' => $link->toString()]);
    }

    // Check if every image_style uses some pipeline.
    $styles = ImageStyle::loadMultiple();
    $style_names = [];
    foreach ($styles as $style) {
      $pipeline = $style->getPipeline();
      if (!isset($pipeline_keys[$pipeline])) {
        $style_names[] = $style->get('label');
      }
    }
    if (count($style_names)) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, [$this->t('ImageApi is installed some image styles is not configured: !list.')], [
        '!list' => implode(', ', $style_names),
        '%link' => $link->toString(),
      ]);
    }

    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS, NULL, ['%link' => $link->toString()]);
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    switch ($type) {
      case AuditMessagesStorageInterface::MSG_TYPE_FAIL:
        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['custom-fail-color'],
          ],
          'message' => [
            // @codingStandardsIgnoreLine
            '#markup' => $this->t($this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_FAIL), $reason->getArguments())
              ->__toString(),
          ],
        ];
        break;

      case AuditMessagesStorageInterface::MSG_TYPE_SUCCESS:
        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['custom-pass-color'],
          ],
          'message' => [
            // @codingStandardsIgnoreLine
            '#markup' => $this->t($this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_SUCCESS), $reason->getArguments())
              ->__toString(),
          ],
        ];
        break;

      default:
        $build = [];
        break;
    }

    return $build;
  }

}
