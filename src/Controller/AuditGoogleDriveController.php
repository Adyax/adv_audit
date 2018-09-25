<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Google_Service_Drive;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Google_Client;
use Google_Service_Drive_DriveFile;

/**
 * The class of the Pdf generation' controller.
 */
class AuditGoogleDriveController {

  /**
   * Public function view.
   */
  public function printToGoogle($adv_audit) {
    /**
     * Get rendered report
     */
    $date_report = date('Y-m-d\TH-i-sO', time());
    $entity_type = 'adv_audit';
    $view_mode = 'pdf';
    $entity_report = \Drupal::entityTypeManager()->getStorage($entity_type)->load($adv_audit->id->value);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $build = $view_builder->view($entity_report, $view_mode);
    $renderer = render($build);

    $headers = [
      'Content-Type: application/html',
      'Charset: utf-8',
    ];

    $client = new Google_Client();
    $client->setClientId('337518780648-m9gli5j24r844gs1b6ukuiphn913ec8s.apps.googleusercontent.com');
    $client->setClientSecret('VmhjkFuPq7dDJ1pM4Yf0o7sj');
    $client->setRedirectUri('http://localhost:8080');
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
    $content = $renderer;
    $file = $driveService->files->create($fileMetadata, array(
      'fields' => 'id'));

    $ret = 'All files: <strong>'.$num.'</strong><br />';
    $ret .= 'File id = ' . $file->id;
    return new Response($ret, 200, $headers);
  }

}
