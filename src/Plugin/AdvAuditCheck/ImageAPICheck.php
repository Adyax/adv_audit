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
   * {@inheritdoc}
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

    $arguments = [
      '%link' => $link->toString(),
    ];

    if (!$this->moduleHandler->moduleExists('imageapi_optimize')) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, $this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_FAIL), $arguments);
    }

    // Check if pipelines were created.
    $pipelines = imageapi_optimize_pipeline_options(FALSE, TRUE);
    $pipeline_keys = array_keys($pipelines);
    if (count($pipeline_keys) === 1 && empty($pipeline_keys[0])) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, $this->t('ImageApi is installed, but any pipeline has not been created.', $arguments));
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
      $arguments['list'] = $style_names;
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, $this->t('ImageApi is installed, some image styles are not configured:', $arguments), $arguments);
    }

    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS, NULL, $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    $build = [];
    if ($type === AuditMessagesStorageInterface::MSG_TYPE_FAIL) {

      $arguments = $reason->getArguments();
      $build = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['custom-fail-color'],
        ],
      ];

      // Render image_style list.
      if (isset($arguments['list'])) {
        $build['list'] = [
          '#theme' => 'item_list',
          '#items' => $arguments['list'],
          '#weight' => 1,
        ];
        unset($arguments['list']);
      }

      $build['message'] = [
        '#weight' => 0,
        '#markup' => $reason->getReason()[0],
      ];

    }

    return $build;
  }

}
