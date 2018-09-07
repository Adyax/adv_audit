<?php

namespace Drupal\adv_audit\Sonar;

use SonarQube\HttpClient\HttpClient;

class SonarHttpClient extends HttpClient {

  public function call($method, $options = [], $parameters = [], array $headers = array()) {
    $path = trim($this->baseUrl, '/') . $method;
    $httpMethod = 'GET';
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
    try {
    } catch (\LogicException $e) {
      throw new ErrorException($e->getMessage());
    } catch (\RuntimeException $e) {
      throw new RuntimeException($e->getMessage());
    }

    if ($hasListeners) {
      foreach ($this->listeners as $listener) {
        $listener->postSend($request, $response);
      }
    }

    return $response;
  }

  /**
   * @param $httpMethod
   * @param $url
   * @return Request
   */
  private function createRequest($httpMethod, $url) {
    $request = new Request($httpMethod);
    $request->setHeaders($this->headers);
    $request->fromUrl($url);

    return $request;
  }

}