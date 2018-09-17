<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;

/**
 * Check if php files can be executed from public directory.
 *
 * @AuditPlugin(
 *  id = "execute_files",
 *  label = @Translation("PHP files in public directory cannot be executed."),
 *  category = "security",
 *  requirements = {},
 * )
 */
class SecurityExecuteFilesPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  /**
   * Drupal's HTTP Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new PerformanceViewsPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $http_client
   *   Access to state service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    global $base_url;
    $url = Url::fromUri('https://www.drupal.org/node/615888', ['attributes' => ['target' => '_blank']]);
    $arguments = [
      '%risk_link' => Link::fromTextAndUrl($this->t('Drupal.org'), $url)
        ->toString(),
    ];

    // Set up test file data.
    $message = 'Advanced audit execute php test ' . date('Ymdhis');
    $content = "<?php\necho '" . $message . "';";
    $file_path = PublicStream::basePath() . '/adv_audit_test.php';

    // Create the test file.
    if ($test_file = fopen('./' . $file_path, 'w')) {
      fwrite($test_file, $content);
      fclose($test_file);
    }

    // Try to access the test file.
    try {
      $response = $this->httpClient->get($base_url . '/' . $file_path);
      $response_body = $response->getBody()->getContents();
      if ($response->getStatusCode() == 200 && $message == $response_body) {
        return $this->fail(NULL, [
          'issues' => [
            'execute_files' => [
              '@issue_title' => 'Files can be executed from drupal file system.',
            ],
          ],
          '%risk_link' => $arguments['%risk_link'],
        ]);
      }
    }
    catch (RequestException $e) {
      return $this->success($arguments);
    }
    return $this->success($arguments);
  }

}
