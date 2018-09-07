<?php
/**
 *
 */

namespace Drupal\adv_audit\Sonar;

use Buzz\Client\Curl;
use Buzz\Exception\RequestException;
use Drupal\Core\Url;
use \SonarQube\Client;
use SonarQube\Exception\RuntimeException;
use SonarQube\HttpClient\Listener\AuthListener;


class SonarClient extends Client {


  const AUTH_BASIC_TOKEN = 'basic_token';

  private $options = [
    'user-agent' => 'php-sonarqube-api (http://github.com/spirit-dev/php-sonarqube-api)',
    'timeout' => 60,
  ];

  private $project;

  protected function methodUrl($method) {
    // Prevent concat issues.
    return trim($this->getBaseUrl(), '/') . '/' . $method;
  }

  public function setProject($data) {
    $this->project = $data;
  }

  public function getProject() {
    return $this->project;
  }

  protected function extend_api($method, $parameters) {
    $url = Url::fromUri($this->methodUrl($method), $parameters)->toString();
    try {
      //      $client = $this->httpClient->call($url);
    } catch (RuntimeException $exception) {

      $i = 0;
    }
  }

  public function api($api_name) {
    $method = FALSE;
    $options = [];
    switch ($api_name) {
      //      case 'dashboard':
      //        $method = 'metrics/domains';
      //        $options = [];
      //        $components = $this->extend_api($method, $options);
      //        $i=0;
      //        break;
      default:
        return parent::api($api_name);
        break;
    }
    if ($method) {
      return $this->extend_api($method, $options);
    }
    return $api;
  }
}