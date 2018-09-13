<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\views\Entity\View;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

/**
 * Checks views are access controlled.
 *
 * @AuditPlugins(
 *   id = "views_access_controlled",
 *   label = @Translation("Checks views are access controlled."),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "critical"
 * )
 */
class SecurityViewsAccessControlled extends AuditBasePlugin implements AdvAuditReasonRenderableInterface, ContainerFactoryPluginInterface {

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
  public function perform() {
    $params = [];

    if (!$this->moduleHandler->moduleExists('views')) {
      return $this->success();
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
      $params['failed_views'] = $findings;
      return $this->fail($this->t('There are number of views with unlimited access.'), $params);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type !== AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      return [];
    }

    $issue_details = $reason->getArguments();
    if (empty($issue_details['failed_views'])) {
      return [];
    }

    $items = [];
    foreach ($issue_details['failed_views'] as $view => $displays) {
      foreach ($displays as $display) {
        $items[] = $view . ': ' . $display;
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

}
