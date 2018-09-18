<?php
/**
 *
 */

namespace Drupal\adv_audit\Sonar;

use Buzz\Exception\RequestException;
use Buzz\Message\Request;
use Buzz\Message\Response;
use \SonarQube\Client;
use  Drupal\adv_audit\Sonar\Api;


class SonarClient extends Client {

  public $dashboard;

  private $project;

  public function setProject($key) {
    $this->project = $key;
  }

  /**
   * @inheritdoc
   */
  protected function methodUrl($method) {
    // Prevent concat issues.
    return trim($this->getBaseUrl(), '/') . '/' . $method;
  }

  /**
   * @inheritdoc
   */
  public function getProject() {
    return $this->project;
  }

  /**
   * @inheritdoc
   */
  protected function extend_api($method, $parameters) {
    return $this->call($method, $parameters);

  }

  /**
   * @inheritdoc
   */
  protected function call($path, array $parameters = [], array $headers = []) {
    return $this->getHttpClient()->get($path, $parameters, $headers);
  }

  /**
   * @inheritdoc
   */
  public function api($api_name) {
    switch ($api_name) {
      case 'dashboard':
        $this->dashboard = new Api\Dashboard($this);
        $method = 'measures/component';
        $options = [
          'component' => $this->project ? $this->project : '',
          'metricKeys' => implode(',', $this->dashboard->metrics),
        ];
        break;

      case 'authentication':
        return new Api\Authentication($this);
      default:
        return parent::api($api_name);
    }
    return $this->extend_api($method, $options);
  }

  /**
   * @inheritdoc
   */
  public function validateRequest($path, $data) {
    $request = new Request('GET');
    $request->fromUrl($path);
    if (isset($data['login']) && isset($data['password'])) {
      $request->addHeader('Authorization: Basic ' . base64_encode($data['login'] . ':' . $data['password']));
    }
    $response = new Response();
    try {
      $this->getHttpClient()->client->send($request, $response);
    } catch (RequestException $e) {
      $response->setContent($e->getMessage());
    }
    return $response;
  }

}
