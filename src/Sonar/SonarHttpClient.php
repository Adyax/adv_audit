<?php

namespace Drupal\adv_audit\Sonar;

use Buzz\Client\ClientInterface;
use SonarQube\HttpClient\HttpClient;
use SonarQube\HttpClient\Message\Request;
use SonarQube\HttpClient\Message\Response;

/**
 * SonarHttpClient Class.
 */
class SonarHttpClient extends HttpClient {

  protected $client;

  /**
   * {@inheritdoc}
   */
  public function __construct($baseUrl, array $options, ClientInterface $client) {
    parent::__construct($baseUrl, $options, $client);
    $this->client = $client;
  }

  /**
   * Request Method.
   *
   * @param string $path
   *   Request path.
   * @param array $parameters
   *   Request parameters.
   * @param string $httpMethod
   *   Request method.
   * @param array $headers
   *   Request headers.
   */
  public function request($path, array $parameters = [], $httpMethod = 'GET', array $headers = []) {
    $path = trim($this->baseUrl . $path, '/');

    $request = $this->createRequest($httpMethod, $path);
    $request->addHeaders($headers);
    $request->setContent(http_build_query($parameters));

    $hasListeners = 0 < count($this->listeners);
    if ($hasListeners) {
      foreach ($this->listeners as $listener) {
        $listener->preSend($request);
      }
    }

    $response = new Response();
    $this->client->send($request, $response);
  }

  /**
   * Create request method.
   *
   * @param string $httpMethod
   *   Request method.
   * @param string $url
   *   Request url.
   *
   * @return \SonarQube\HttpClient\Message\Request
   *   Request object.
   */
  private function createRequest($httpMethod, $url) {
    $request = new Request($httpMethod);
    $request->setHeaders($this->headers);
    $request->fromUrl($url);
    return $request;
  }

}
