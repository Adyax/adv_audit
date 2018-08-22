<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\user\Entity\User;

/**
 * Checks if any admin pages and other unused default Drupal pages are available for anonymous users.
 *
 * @AdvAuditCheck(
 *   id = "admin_pages_access",
 *   label = @Translation("Admin pages access check"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class AdminPagesAccessCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, ContainerFactoryPluginInterface {

  /**
   * Predefined URLs list.
   */
  private const URLS = [
    '/node',
    '/node/add',
    '/taxonomy/term/{entity:taxonomy_term}',
    '/admin/structure/taxonomy/add',
    '/admin/structure/taxonomy/manage/{entity:taxonomy_vocabulary}/add',
    '/admin/people/create',
  ];

  /**
   * The state service object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Symfony\Component\HttpKernel\HttpKernelInterface definition.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current user.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * The search server storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $services) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $services['state_service'];
    $this->httpKernel = $services['http_kernel_service'];
    $this->request = $services['request_service'];
    $this->currentUser = $services['current_user_service'];
    $this->moduleHandler = $services['module_handler_service'];
    $this->session = $services['session_service'];
    $this->entityTypeManager = $services['etm'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $services = [
      'state_service' => $container->get('state'),
      'http_kernel_service' => $container->get('http_kernel'),
      'request_service' => $container->get('request_stack')->getCurrentRequest(),
      'current_user_service' => $container->get('current_user'),
      'module_handler_service' => $container->get('module_handler'),
      'session_service' => $container->get('session'),
      'etm' => $container->get('entity_type.manager'),
    ];
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $services
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {
    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URLs for access checking'),
      '#description' => t(
        'Place one URL(relative) per line as relative with preceding slash, i.e /path/to/page.
         <br />Predefined URLs: @urls
         <br />Entity id placeholder(one per URL) can be used in format {entity:<entity_type>}, i.e. /taxonomy/term/{entity:taxonomy_term}',
        ['@urls' => implode(', ', self::URLS)]
      ),
      '#default_value' => $this->state->get($this->buildStateConfigKey()),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormValidate($form, FormStateInterface $form_state) {
    $value_name = ['additional_settings', 'plugin_config', 'urls'];
    $urls = $this->parseLines($form_state->getValue($value_name));

    foreach ($urls as $url) {
      if (!UrlHelper::isValid($url) || substr($url, 0, 1) !== '/') {
        $form_state->setErrorByName('additional_settings][plugin_config][urls', $this->t('Urls should be given as relative with preceding slash.'));
        break;
      }

      if (in_array($url, self::URLS)) {
        $form_state->setErrorByName(
          'additional_settings][plugin_config][urls',
          $this->t('Url @url already stored as predefined.', ['@url' => $url])
        );
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    $value_name = ['additional_settings', 'plugin_config', 'urls'];
    $value = $form_state->getValue($value_name);
    $this->state->set($this->buildStateConfigKey(), $value);
  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $status = AuditResultResponseInterface::RESULT_PASS;
    $params = [];

    $uid = $this->currentUser->id();
    $this->switchToAnonymousUser();

    try {
      $user_urls = $this->parseLines($this->state->get($this->buildStateConfigKey()));
      $urls = array_merge(self::URLS, $user_urls);

      foreach ($urls as $url) {
        $url = $this->replaceEntityPlaceholder($url);

        $sub_request = Request::create($this->request->getSchemeAndHttpHost() . $url, 'GET');
        if ($this->request->getSession()) {
          $sub_request->setSession($this->request->getSession());
        }
        $sub_response = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
        if ($sub_response->getStatusCode() != 403 && $sub_response->getStatusCode() != 404) {
          $params['failed_urls'][] = $url;
          $status = AuditResultResponseInterface::RESULT_FAIL;
        }
      }

      $this->switchBack($uid);
    }
    catch (\Exception $e) {
      $this->switchBack($uid);
      throwException($e);
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

    $key = 'failed_urls';

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
    $message['msg'][$markup_key] = $this->t('There are URLs that should not be available for anonymous user.')->__toString();

    $list = [
      '#theme' => 'item_list',
    ];
    $items = [];
    foreach ($arguments[$key] as $url) {
      $item[$markup_key] = $url;
      $items[] = $item;
    }
    $list['#items'] = $items;

    return [$message, $list];
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  private function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.config.urls';
  }

  /**
   * Parses textarea lines into array.
   *
   * @param string $lines
   *   Textarea content.
   *
   * @return array
   *   The textarea lines.
   */
  private function parseLines($lines) {
    $lines = explode("\n", $lines);

    if (!count($lines)) {
      return [];
    }
    $lines = array_filter($lines, 'trim');

    return str_replace("\r", "", $lines);
  }

  /**
   * Switch to anonymous user.
   */
  private function switchToAnonymousUser() {
    $anonymous = User::load(0);

    $this->moduleHandler->invokeAll('user_logout', [$this->currentUser]);
    $this->currentUser->setAccount($anonymous);
    $this->session->set('uid', $anonymous->id());
    $this->moduleHandler->invokeAll('user_login', [$anonymous]);
  }

  /**
   * Switchback to old user.
   *
   * @param int $uid
   *   User uid.
   */
  private function switchBack($uid) {
    $current_user = User::load($uid);
    $anonymous = User::load(0);

    $this->moduleHandler->invokeAll('user_logout', [$anonymous]);
    $this->currentUser->setAccount($current_user);
    $this->session->set('uid', $uid);
    $this->moduleHandler->invokeAll('user_login', [$current_user]);
  }

  /**
   * Replace entity placeholder.
   *
   * @param string $url
   *   URL to be processed.
   *
   * @return string
   *   Processed URL.
   */
  private function replaceEntityPlaceholder($url) {
    preg_match_all('/{entity:(.*?)}/', $url, $entity_type);
    if (empty($entity_type[1][0])) {
      return $url;
    }

    $storage = $this->entityTypeManager->getStorage($entity_type[1][0]);
    $query = $storage->getQuery();
    $query->range(0, 1);
    $res = $query->execute();

    $entity_id = count($res) ? reset($res) : NULL;
    if (empty($entity_id)) {
      return $url;
    }

    return preg_replace('/{entity:.*?}/', $entity_id, $url);
  }

}
