<?php

namespace Drupal\adv_audit\Controller;

use Google_Service_Drive;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
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
    $client->setClientId('337518780648-e2t289mbufr3m81oe0v7caa1qgdg0pt9.apps.googleusercontent.com');
    $client->setClientSecret('m1PZSJJ9NyEKM9DJdc5AlL4p');
    $client->authorize();
    $driveService = new Google_Service_Drive($client);
    $fileMetadata = new Google_Service_Drive_DriveFile(array(
      'name' => 'Auditor Report ',
      'mimeType' => 'application/vnd.google-apps.document'));
    $content = $renderer;
    $file = $driveService->files->create($fileMetadata, array(
      'data' => $content,
      'mimeType' => 'application/html',
      'uploadType' => 'multipart',
      'fields' => 'id'));
    printf("File ID: %s\n", $file->id);

    return new Response($renderer, 200, $headers);
  }



}
