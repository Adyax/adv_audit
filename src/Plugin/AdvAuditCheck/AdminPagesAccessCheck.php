<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\Client;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks if access to admin pages is forbidden for anonymous users.
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
class AdminPagesAccessCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

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
   * The Entity type manegr.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Returns the default http client.
   *
   * @var \GuzzleHttp\Client
   *   A guzzle http client instance.
   */
  protected $httpClient;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, EntityTypeManagerInterface $etm, Client $client, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->entityTypeManager = $etm;
    $this->httpClient = $client;
    $this->request = $request;
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
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('request_stack')->getCurrentRequest()
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
  public function configFormValidate(array $form, FormStateInterface $form_state) {
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
  public function configFormSubmit(array $form, FormStateInterface $form_state) {
    $value_name = ['additional_settings', 'plugin_config', 'urls'];
    $value = $form_state->getValue($value_name);
    $this->state->set($this->buildStateConfigKey(), $value);
  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $params = [];

    $user_urls = $this->parseLines($this->state->get($this->buildStateConfigKey()));
    $urls = empty($user_urls) ? self::URLS : $user_urls;

    foreach ($urls as $url) {
      $url = $this->replaceEntityPlaceholder($url);

      try {
        $response = $this->httpClient->get($this->request->getSchemeAndHttpHost() . $url);
        if ($response->getStatusCode() == 200) {
          // Secure check fail: the page should not be accessible.
          $params['failed_urls'][] = $url;
        }
      }
      catch (\Exception $e) {
        $code = $e->getCode();
        if (!empty($code) && in_array($code, [401, 403, 404])) {
          // It's good code.
          $params['failed_urls'][] = $url;
          continue;
        }
        if ($code > 500) {
          // Log pages that produce server error.
          $params['failed_urls'][] = $url;
        }
      }
    }

    if (!empty($params['failed_urls'])) {
      $issues = $this->getIssues($params['failed_urls']);
      return $this->fail(NULL, ['issues' => $issues]);
    }
    return $this->success();
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
   * Replace entity placeholder.
   *
   * @param string $url
   *   URL to be processed.
   *
   * @return string
   *   Processed URL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
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

  /**
   * Get list of issues.
   *
   * @param array $params
   *   List failed URLs.
   *
   * @return array
   *   List of issues.
   */
  private function getIssues(array $params) {
    $issues = [];
    foreach ($params as $failed_url) {
      $issues[$failed_url] = [
        '@issue_title' => 'Url "@url" should not be available for anonymous user',
        '@url' => $failed_url,
      ];
    }

    return $issues;
  }

}
