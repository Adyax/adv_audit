<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if php files can be executed from public directory.
 *
 * @AdvAuditCheck(
 *  id = "temporary_files",
 *  label = @Translation("No sensitive temporary files were found."),
 *  category = "security",
 *  severity = "normal",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class TemporaryFiles extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal's kernel.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * Core render service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a new PerformanceViewsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\DrupalKernel $kernel
   *   Provide general information about drupal installation.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Provide access to render service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DrupalKernel $kernel, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->kernel = $kernel;
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
      $container->get('kernel'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $status = AuditResultResponseInterface::RESULT_PASS;
    $arguments = [];

    // Get list of files from the site directory.
    $files = [];
    $site_path = $this->kernel->getSitePath() . '/';
    $dir = scandir($site_path);
    foreach ($dir as $file) {
      // Set full path to only files.
      if (!is_dir($file)) {
        $files[] = $site_path . $file;
      }
    }

    // Analyze the files' names.
    $findings = [];
    foreach ($files as $path) {
      $matches = [];
      if (file_exists($path) && preg_match('/.*(~|\.sw[op]|\.bak|\.orig|\.save)$/', $path, $matches) !== FALSE && !empty($matches)) {
        // Found a temporary file.
        $findings[] = $path;
      }
    }

    if (count($findings)) {
      $status = AuditResultResponseInterface::RESULT_FAIL;
      $render_array = [
        '#theme' => 'item_list',
        '#items' => $findings,
      ];
      $arguments['%files'] = $this->renderer->render($render_array);
    }

    return new AuditReason($this->id(), $status, NULL, $arguments);

  }

}
