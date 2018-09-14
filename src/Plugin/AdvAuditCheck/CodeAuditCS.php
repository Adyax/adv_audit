<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Checs code quality by using Code Sniffer.
 *
 * @AdvAuditCheck(
 *   id = "code_audit_cs",
 *   label = @Translation("Code audit by CodeSniffer"),
 *   category = "code_review",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class CodeAuditCS extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

  /**
   * Stream warapper service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $swm;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystem $file_system, StreamWrapperManager $swm, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->swm = $swm;
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
      $container->get('file_system'),
      $container->get('stream_wrapper_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)  {
    $settings = $this->getSettings();

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Relative path to perform CS check'),
      '#default_value' => $settings['path'],
    ];

    $form['exts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('File extensions should be checked'),
      '#description' => $this->t('Place one extension per line.'),
      '#default_value' => $settings['exts'],
    ];

    $form['ignores'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ignored subdirectories'),
      '#description' => $this->t('Place one directory per line.'),
      '#default_value' => $settings['ignores'],
    ];

    $form['output'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Output directory'),
      '#description' => $this->t('Subdirectory in public:// for storing results.'),
      '#default_value' => $settings['output'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    if (!function_exists('exec')) {
      return $this->skip($this->t('exec function is disabled.'));
    }

    $scheme = $this->configFactory()
        ->get('system.file')
        ->get('default_scheme') . '://';
    $drupal_failed = FALSE;
    $drupal_practice_failed = FALSE;
    $result = [];

    $settings = $this->getSettings();

    $exts = implode(',',$this->parseLines($settings['exts']));
    $path = $settings['path'];
    $ignores = implode(',', $this->parseLines($settings['ignores']));
    $output = $settings['output'];

    if (!$this->fileSystem->realpath($scheme . $output)) {
      $this->fileSystem->mkdir($scheme . $output, NULL, TRUE);
    }

    $file_rel_path = $output . '/' . md5('phpcs_D_' . time()) . '.txt';
    $filepath = $this->fileSystem->realpath($scheme) . '/' . $file_rel_path;

    $phpcs = $this->getCsDir();

    $phpcs_cmd = "php {$phpcs} --standard=Drupal --extensions={$exts} {$path} --ignore={$ignores} > {$filepath}";
    exec($phpcs_cmd);
    $this->fileSystem->chmod($filepath, 0744);

    if (file_exists($filepath) && filesize($filepath) > 0) {
      $drupal_failed = TRUE;
      $wrapper = $this->swm->getViaUri($scheme);
      $url = $wrapper->getExternalUrl() . $file_rel_path;
      $drupal_link = Link::fromTextAndUrl($this->t('Drupal'), URL::fromUri($url))
        ->toString();
      $result['issues']['drupal_standard'] = [
        '@issue_title' => 'Problems with Drupal coding standards has been found in your code. You can review them by link @link',
        '@link' => $drupal_link,
      ];
    }

    $file_rel_path = $output . '/' . md5('phpcs_DP_' . time()) . '.txt';
    $filepath = $this->fileSystem->realpath($scheme) . '/' . $file_rel_path;

    $phpcs_cmd = "php {$phpcs} --standard=DrupalPractice --extensions={$exts} {$path} --ignore={$ignores} > {$filepath}";
    exec($phpcs_cmd);
    $this->fileSystem->chmod($filepath, 0744);

    if (file_exists($filepath) && filesize($filepath) > 0) {
      $drupal_practice_failed = TRUE;
      $wrapper = $this->swm->getViaUri($scheme);
      $url = $wrapper->getExternalUrl() . $file_rel_path;
      $drupal_practice_link = Link::fromTextAndUrl($this->t('DrupalPractice'), URL::fromUri($url))
        ->toString();
      $result['issues']['drupal_best_practice'] = [
        '@issue_title' => 'Problems with Drupal best practices has been found in your code. You can review them by @link',
        '@link' => $drupal_practice_link,
      ];
    }

    if ($drupal_failed || $drupal_practice_failed) {
      return $this->fail($this->t('There are code sniffer issues.'), $result);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();

    if (!file_exists($this->getCsDir())) {
      throw new RequirementsException(
        $this->t('CodeSniffer is not installed'),
        [$this->pluginDefinition['requirements']['module']]
      );
    }
  }

  /**
   * Returns path to phpcs script.
   *
   * @return string
   *   Path to phpcs script.
   */
  private function getCsDir() {
    $vendor_dir = is_dir(DRUPAL_ROOT . '/vendor') ? DRUPAL_ROOT . '/vendor' : DRUPAL_ROOT . '/../vendor';
    $phpcs_path = $vendor_dir . '/squizlabs/php_codesniffer/scripts/phpcs';

    return $phpcs_path;
  }

}
