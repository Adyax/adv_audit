<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\File\FileSystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\adv_audit\Exception\RequirementsException;

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
class CodeAuditCS extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * Relative path to perform CS check.
   */
  const PATH_TO_CHECK = 'modules';

  /**
   * File extensions should be checked.
   */
  const FILE_EXTS = [
    'php',
    'module',
    'inc',
    'install',
    'test',
    'profile',
    'theme',
    'js',
    'css',
    'info',
    'txt',
    'md',
    'yml',
  ];

  /**
   * Ignored subdirectories.
   */
  const IGNORES = [
    '*/vendor/*',
  ];

  /**
   * Subdirectory in public:// for storing results.
   */
  const OUTPUT_URI = 'adv_audit/cs';

  /**
   * The state service object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystem $file_system, StreamWrapperManager $swm, StateInterface $state, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->swm = $swm;
    $this->state = $state;
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
      $container->get('state'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {
    $state_keys = $this->buildStateConfigKeys();

    $default_value = !empty($this->state->get($state_keys['path'])) ?
      $this->state->get($state_keys['path']) : self::PATH_TO_CHECK;
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Relative path to perform CS check'),
      '#default_value' => $default_value,
    ];

    $default_value = !empty($this->state->get($state_keys['exts'])) ?
      $this->state->get($state_keys['exts']) : implode("\r\n", self::FILE_EXTS);
    $form['exts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('File extensions should be checked'),
      '#description' => $this->t('Place one extension per line.'),
      '#default_value' => $default_value,
    ];

    $default_value = !empty($this->state->get($state_keys['ignores'])) ?
      $this->state->get($state_keys['ignores']) : implode("\r\n", self::IGNORES);
    $form['ignores'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ignored subdirectories'),
      '#description' => $this->t('Place one directory per line.'),
      '#default_value' => $default_value,
    ];

    $default_value = !empty($this->state->get($state_keys['output'])) ?
      $this->state->get($state_keys['output']) : self::OUTPUT_URI;
    $form['output'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Output directory'),
      '#description' => $this->t('Subdirectory in public:// for storing results.'),
      '#default_value' => $default_value,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit(array $form, FormStateInterface $form_state) {
    $base = ['additional_settings', 'plugin_config'];
    $state_keys = $this->buildStateConfigKeys();

    foreach ($state_keys as $state_key => $state_value) {
      $value = $form_state->getValue(array_merge($base, [$state_key]));
      $this->state->set($state_value, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    if (!function_exists('exec')) {
      return $this->skip($this->t('exec function is disabled.'));
    }

    $scheme = $this->configFactory()->get('system.file')->get('default_scheme') . '://';
    $drupal_failed = FALSE;
    $drupal_practice_failed = FALSE;

    $state_keys = $this->buildStateConfigKeys();

    $exts = !empty($this->state->get($state_keys['exts'])) ?
      implode(',', $this->parseLines($this->state->get($state_keys['exts']))) : implode(',', self::FILE_EXTS);
    $path = !empty($this->state->get($state_keys['path'])) ?
      $this->state->get($state_keys['path']) : self::PATH_TO_CHECK;
    $ignores = !empty($this->state->get($state_keys['ignores'])) ?
      implode(',', $this->parseLines($this->state->get($state_keys['ignores']))) : implode(',', self::IGNORES);
    $output = !empty($this->state->get($state_keys['output'])) ?
      $this->state->get($state_keys['output']) : self::OUTPUT_URI;

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
      $drupal_link = $this->t('<a href="@url" download>Drupal</a>', ['@url' => $url]);
    }

    $file_rel_path = $output . '/' . md5('phpcs_DP_' . time()) . '.txt';
    $filepath = $this->fileSystem->realpath($scheme) . '/' . $file_rel_path;

    $phpcs_cmd = "php {$phpcs} --standard=DrupalPractice --extensions={$exts} {$path} --ignore={$ignores} > {$filepath}";
    exec($phpcs_cmd);
    $this->fileSystem->chmod($filepath, 0744);

    if (file_exists($filepath) && filesize($filepath) > 0) {
      $drupal_failed = TRUE;
      $wrapper = $this->swm->getViaUri($scheme);
      $url = $wrapper->getExternalUrl() . $file_rel_path;
      $drupal_practice_link = $this->t('<a href="@url" download>DrupalPractice</a>', ['@url' => $url]);
    }

    if ($drupal_failed || $drupal_practice_failed) {
      return $this->fail($this->t('There are code sniffer issues.'), [
        '@drupal' => $drupal_link,
        '@drupal_practice' => $drupal_practice_link,
      ]);
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
        $this->pluginDefinition['requirements']['module']
      );
    }
  }

  /**
   * Build keys array for access to stored values from config.
   *
   * @return array
   *   The generated keys.
   */
  private function buildStateConfigKeys() {
    return [
      'path' => 'adv_audit.plugin.' . $this->id() . '.config.path',
      'exts' => 'adv_audit.plugin.' . $this->id() . '.config.exts',
      'ignores' => 'adv_audit.plugin.' . $this->id() . '.config.ignores',
      'output' => 'adv_audit.plugin.' . $this->id() . '.config.output',
    ];
  }

  /**
   * Returns path to phpcs script.
   *
   * @return string
   *   Path to phpcs script.
   */
  private function getCsDir() {
    $moduel_path = $this->fileSystem->realpath($this->moduleHandler->getModule('adv_audit')->getPath());
    $phpcs_path = 'vendor/squizlabs/php_codesniffer/scripts/phpcs';

    return $moduel_path . '/' . $phpcs_path;
  }

}
