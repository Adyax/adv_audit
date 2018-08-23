<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\views\Entity\View;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

/**
 * Checks views are access controlled.
 *
 * @AdvAuditCheck(
 *   id = "views_access_controlled",
 *   label = @Translation("Checks views are access controlled."),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "critical"
 * )
 */
class ViewsAccessControlled extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform($condition = NULL) {
    $status = AuditResultResponseInterface::RESULT_PASS;
    $params = [];

    if (!$this->moduleHandler->moduleExists('views')) {
      return new AuditReason($this->id(), $status, NULL, $params);
    }

    $findings = [];

    $views = View::loadMultiple();

    foreach ($views as $view) {
      if ($view->status()) {
        foreach ($view->get('display') as $display_name => $display) {
          $access = &$display['display_options']['access'];
          if (isset($access) && $access['type'] == 'none') {
            $findings[$view->id()][] = $display_name;
          }
        }
      }
    }

    if (!empty($findings)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $params['failed_views'] = $findings;
    }

    return new AuditReason($this->id(), $status, NULL, $params);
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type != AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      return [];
    }

    $key = 'failed_views';

    $arguments = $reason->getArguments();
    if (empty($arguments[$key])) {
      return [];
    }

    $markup_key = '#markup';
    $message = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['fail-message'],
      ],
    ];
    $message['msg'][$markup_key] = $this->t('There are views that should not have unlimited access.');

    $list = [
      '#theme' => 'item_list',
    ];
    $items = [];
    foreach ($arguments[$key] as $view => $displays) {
      foreach ($displays as $display) {
        $item[$markup_key] = $view . ':' . $display;
        $items[] = $item;
      }
    }
    $list['#items'] = $items;

    return [$message, $list];
  }

}
