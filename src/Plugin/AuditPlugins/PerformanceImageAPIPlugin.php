<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;

/**
 * Provide imageApi optimize check.
 *
 * @AuditPlugin(
 *  id = "imageapi_optimize_check",
 *  label = @Translation("ImageAPI Optimize"),
 *  category = "performance",
 *  requirements = {},
 * )
 */
class PerformanceImageAPIPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

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
   * Constructs a new PerformanceImageAPI object.
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
    $arguments = [
      '%link' => $link->toString(),
    ];
    $message = NULL;

    if (!$this->moduleHandler->moduleExists('imageapi_optimize')) {
      $message = $this->t('The ImageApi module is not installed.');
      return $this->fail($message, $arguments);
    }

    // Check if pipelines were created.
    $pipelines = imageapi_optimize_pipeline_options(FALSE, TRUE);
    $pipeline_keys = array_keys($pipelines);
    if (count($pipeline_keys) === 1 && empty($pipeline_keys[0])) {
      $message = $this->t('ImageApi is installed, but no pipeline is created.');
      return $this->fail($message, $arguments);
    }

    // Check if every image_style uses some pipeline.
    $styles = ImageStyle::loadMultiple();
    foreach ($styles as $style) {
      // Get pipeline for image style.
      // @see Drupal\imageapi_optimize\Entity\ImageStyleWithPipeline::getPipeline().
      $pipeline = $style->getPipeline();

      // Check if image_style's pipeline exist.
      if (!isset($pipelines[$pipeline])) {
        $arguments['issues'][$style->get('label')] = [
          '@issue_title' => 'Image optimize isn\'t configured for @style_name image style',
          '@style_name' => $style->get('label'),
        ];
      }
    }
    if (count($arguments['issues'])) {
      $message = $this->t('ImageApi is installed, some image styles are not configured:');
      return $this->fail($message, $arguments);
    }

    return $this->success($arguments);
  }

}
