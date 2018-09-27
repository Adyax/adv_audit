<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Google_Client;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive;

/**
 * Get html report.
 */
class AuditHtmlReportController {

  /**
   * Get html report..
   */
  public function getReportHtml($adv_audit) {

    $date_report = date('Y-m-d\TH-i-sO', time());
    $entity_type = 'adv_audit';
    $view_mode = 'html';
    $entity_report = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->load($adv_audit->id->value);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $build = $view_builder->view($entity_report, $view_mode);
    $renderer = render($build);

    $stylesheet = file_get_contents(DRUPAL_ROOT . '/' . drupal_get_path('module', 'adv_audit') . '/css/view_html_results.css');

    $renderer = '<style media="all">' . $stylesheet . '</style>' . $renderer;

    $renderer = preg_replace('/\\n/', '', $renderer);

    $headers = [
      'Content-Type: text/html',
      'charset=utf-8'
    ];

    $client = new Google_Client();
    $client->setClientId('855434985644-ttd13eu3ki463g4al0e46j1qbqgj77em.apps.googleusercontent.com');
    $client->setClientSecret('KRctuF-nR5s5K8eNEI0-rc8Y');
    $client->setRedirectUri('http://192.168.1.49');
    $client->setScopes(array('https://www.googleapis.com/auth/drive'));
    $driveService = new Google_Service_Drive($client);
    if (!isset($_REQUEST['code'])) {
      $authUrl = $client->createAuthUrl();
      $redirect = new TrustedRedirectResponse($authUrl);
      $redirect->send();
    }

    $accessToken = $client->authenticate($_REQUEST['code']);
    $client->setAccessToken($accessToken);
    // take maximum number of elements
    $list = $driveService->files->listFiles()->getFiles();
    $num = count($list);
    $fileMetadata = new Google_Service_Drive_DriveFile(array(
      'name' => 'Auditor Report - ' . $date_report,
      'mimeType' => 'application/vnd.google-apps.document'));

    $file = $driveService->files->create($fileMetadata, array(
      'fields' => 'id'));

    $ret = 'All files: <strong>'.$num.'</strong><br />';
    $ret .= 'File id = ' . $file->id;

    return new Response($renderer, 200, $headers);
  }

}
