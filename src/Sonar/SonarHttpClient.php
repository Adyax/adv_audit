<?php

namespace Drupal\adv_audit\Sonar;

use Buzz\Client\ClientInterface;
use SonarQube\HttpClient\HttpClient;
use SonarQube\HttpClient\Message\Request;

class SonarHttpClient extends HttpClient {

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
   * @param $httpMethod
   * @param $url
   *
   * @return Request
   */
  private function createRequest($httpMethod, $url) {
    $request = new Request($httpMethod);
    $request->setHeaders($this->headers);
    $request->fromUrl($url);
    return $request;
  }

}
