<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\views\Entity\View;

/**
 * Checks views are access controlled.
 *
 * @AuditPlugin(
 *   id = "views_access_controlled",
 *   label = @Translation("Checks views are access controlled."),
 *   category = "security",
 *   requirements = {},
 * )
 */
class SecurityViewsAccessControlledPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

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
      $issues = [];

      foreach ($findings as $view => $displays) {
        foreach ($displays as $display) {
          $issues[] = [
            '@issue_title' => 'The @view:@display has no access restrictions.',
            '@view' => $view,
            '@display' => $display,
          ];
        }
      }
      return $this->fail(NULL, ['issues' => $issues]);
    }

    return $this->success();
  }

}
