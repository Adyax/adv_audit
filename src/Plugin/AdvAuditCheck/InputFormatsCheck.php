<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Input Formats Check plugin class.
 *
 * @AdvAuditCheck(
 *   id = "input_formats_check",
 *   label = @Translation("Allowed HTML tags in text formats"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class InputFormatsCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {
  /**
   * The state service object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Interface for working with drupal module system.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Array of issues with input format settings.
   *
   * @var array
   */
  protected $issues;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('state'),
      $container->get('module_handler')
    );
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.additional-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {
    $form = [];
    $settings = $this->getPerformSettings();

    // Get the user roles.
    $roles = user_roles();
    $options = [];
    foreach ($roles as $rid => $role) {
      $options[$rid] = $role->label();
    }

    $form['untrusted_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Untrusted roles.'),
      '#default_value' => $settings['untrusted_roles'],
      '#options' => $options,
    ];
    $form['unsafe_tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Unsafe tags'),
      '#default_value' => $settings['unsafe_tags'],
      '#description' => $this->t('List of unsafe HTML tags, separated with coma without spaces.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit(array $form, FormStateInterface $form_state) {
    $value = $form_state->getValue('additional_settings');
    foreach ($value['plugin_config']['untrusted_roles'] as $key => $untrusted_role) {
      if (!$untrusted_role) {
        unset($value['plugin_config']['untrusted_roles'][$key]);
      }
    }
    $this->state->set($this->buildStateConfigKey(), $value['plugin_config']);
  }

  /**
   * Get settings for perform task.
   */
  protected function getPerformSettings() {
    $settings = $this->state->get($this->buildStateConfigKey());
    return !is_null($settings) ? $settings : $this->getDefaultPerformSettings();
  }

  /**
   * Get default settings.
   */
  protected function getDefaultPerformSettings() {
    return [
      'untrusted_roles' => ['anonymous', 'authenticated'],
      'unsafe_tags' => 'applet,area,audio,base,basefont,body,button,comment,embed,eval,form,frame,frameset,head,html,iframe,image,img,input,isindex,label,link,map,math,meta,noframes,noscript,object,optgroup,option,param,script,select,style,svg,table,td,textarea,title,video,vmlframe',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    // If filter is not enabled return with INFO.
    if (!$this->moduleHandler->moduleExists('filter')) {
      return $this->skip($this->t('Module filter is not enabled.'));
    }

    $formats = filter_formats();
    $settings = $this->getPerformSettings();
    $untrusted_roles = $settings['untrusted_roles'];
    $unsafe_tags = explode(',', $settings['unsafe_tags']);

    foreach ($formats as $format) {
      $format_roles = array_keys(filter_get_roles_by_format($format));
      $intersect = array_intersect($format_roles, $untrusted_roles);

      if (!empty($intersect)) {
        $this->auditInputFormats($format, $unsafe_tags);
      }
    }

    if (count($this->issues)) {
      return $this->fail(NULL, ['issues' => $this->issues]);
    }
    return $this->success();

  }

  /**
   * Audit view input formats.
   */
  public function auditInputFormats($format, $unsafe_tags) {
    // Untrusted users can use this format.
    // Check format for enabled HTML filter.
    $filter_html_enabled = FALSE;
    if ($format->filters()->has('filter_html')) {
      $filter_html_enabled = $format->filters('filter_html')
        ->getConfiguration()['status'];
    }
    $filter_html_escape_enabled = FALSE;
    if ($format->filters()->has('filter_html_escape')) {
      $filter_html_escape_enabled = $format->filters('filter_html_escape')
        ->getConfiguration()['status'];
    }

    if ($filter_html_enabled) {
      $filter = $format->filters('filter_html');

      // Check for unsafe tags in allowed tags.
      $allowed_tags = array_keys($filter->getHTMLRestrictions()['allowed']);
      foreach (array_intersect($allowed_tags, $unsafe_tags) as $tag) {
        // Found an unsafe tag.
        $this->issues[$format->id() . '.' . $format->label()] = [
          '@issue_title' => "It is recommended you remove the @tag tag from @format format for untrusted roles.",
          '@tag' => $tag,
          '@format' => $format->label(),
        ];
      }
    }
    elseif (!$filter_html_escape_enabled) {
      // Format is usable by untrusted users but does not contain the HTML
      // Filter or the HTML escape.
      $this->issues[$format->id() . '.' . $format->label()] = [
        '@issue_title' => '@format format is usable by untrusted roles and do not filter or escape allowed HTML tags.',
        '@format' => $format->label(),
      ];
    }
  }

}
