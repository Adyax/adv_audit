<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\Client;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks if access to admin pages is forbidden for anonymous users.
 *
 * @AuditPlugin(
 *   id = "admin_pages_access",
 *   label = @Translation("Admin pages access check"),
 *   category = "security",
 *   requirements = {},
 * )
 */
class SecurityAdminPagesAccessPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

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
   * Provide router.route_provider service.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   *   RouteProvider instance.
   */
  protected $routerProvider;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $etm, Client $client, Request $request, RouteProvider $routerProvider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $etm;
    $this->httpClient = $client;
    $this->request = $request;
    $this->routerProvider = $routerProvider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URLs for access checking'),
      '#description' => t(
        'Place one URL(relative) per line as relative with preceding slash, i.e /path/to/page.'
      ),
      '#default_value' => $settings['urls'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $urls = $this->parseLines($values['urls']);
    foreach ($urls as $url) {
      if ((!UrlHelper::isValid($url) && !$this->routerProvider->getRoutesByPattern($url)) || substr($url, 0, 1) !== '/') {
        $form_state->setErrorByName('urls', $this->t('Urls should be given as relative with preceding slash.'));
        break;
      }
    }
  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $params = [];
    $settings = $this->getSettings();
    $urls = $this->parseLines($settings['urls']);
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
          // It's good code, secure check OK.
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
   * Replace entity placeholder.
   *
   * @param string $url
   *   URL to be processed.
   *
   * @return string
   *   Processed URL.
   *
   * @throws \Exception
   *   Thrown exceptions.
   */
  private function replaceEntityPlaceholder($url) {

    try {
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
    catch (\Exception $e) {
      return $url;
    }

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
        '@issue_title' => 'Url "@url" should not be available for anonymous user.',
        '@url' => $failed_url,
      ];
    }

    return $issues;
  }

}
