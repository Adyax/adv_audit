<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Google_Client;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive;

/**
 * Get html report and upload it to Google Drive.
 */
class AuditHtmlReportController implements ContainerInjectionInterface {

  protected $configFactory;

  /**
   * AuditHtmlReportController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\adv_audit\Controller\AuditHtmlReportController
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Build report html.
   *
   * @param \Drupal\adv_audit\Entity\AuditEntity $adv_audit
   *
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getReportHtml($adv_audit) {

    // Get markup of report.
    $date_report = date('Y-m-d\TH-i-sO', time());
    $entity_type = 'adv_audit';
    $view_mode = 'html';
    $entity_report = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->load($adv_audit->id->value);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $build = $view_builder->view($entity_report, $view_mode);
    $renderer = render($build);

    // We need to include inline styles for correct converting html to google docs.
    $stylesheet = file_get_contents(DRUPAL_ROOT . '/' . drupal_get_path('module', 'adv_audit') . '/css/view_html_results.css');
    $renderer = '<style media="all">' . $stylesheet . '</style>' . $renderer;
    $renderer = preg_replace('/\\n/', '', $renderer);

    // Get settings.
    $google_id = $this->configFactory->getEditable('adv_audit.settings')
    ->get('google_id');
    $google_secret = $this->configFactory->getEditable('adv_audit.settings')
    ->get('google_secret');
    $google_folder = $this->configFactory->getEditable('adv_audit.settings')
      ->get('google_folder');

    // Google ID and Password for API are required
    if (empty($google_id) || empty($google_secret)) {
      $redirect = new TrustedRedirectResponse(\Drupal::urlGenerator()
        ->generateFromRoute('adv_audit.google'));
      $redirect->send();
    }

    // Set client.
    $client = new Google_Client();
    $client->setClientId($google_id);
    $client->setClientSecret($google_secret);

    // Build url for redirect from GD API.
    $redirect_url = \Drupal::urlGenerator()->generateFromRoute('adv_audit', [], ['absolute' => TRUE]);
    $redirect_url .= '/' . $adv_audit->id->value . '/html';
    $client->setRedirectUri($redirect_url);
    $client->setScopes(['https://www.googleapis.com/auth/drive']);
    $driveService = new Google_Service_Drive($client);

    // Get token.
    if (!isset($_REQUEST['code'])) {
      $authUrl = $client->createAuthUrl();
      $redirect = new TrustedRedirectResponse($authUrl);
      $redirect->send();
    }
    $accessToken = $client->fetchAccessTokenWithAuthCode($_REQUEST['code']);
    $client->setAccessToken($accessToken);
    $list = $driveService->files->listFiles()->getFiles();
    $folder_id = NULL;
    foreach ($list as $file) {
      if ($file->getName() === $google_folder) {
        $folder_id = $file->getId();
      }

    }
    $fileMetadata = new Google_Service_Drive_DriveFile([
      'name' => 'Auditor Report - ' . $date_report . '.html',
      'parents' => [$folder_id],
    ]);

    $driveService->files->create($fileMetadata, [
      'data' => $renderer,
      'mimeType' => 'text/html',
      'uploadType' => 'multipart',
      'fields' => 'id',
    ]);

    return new Response('Report has been uploaded', 200);
  }

}
