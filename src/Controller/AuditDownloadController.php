<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AuditDownloadController.
 *
 * @package Drupal\adv_audit\Controller
 */
class AuditDownloadController extends ControllerBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * AuditDownloadController constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager) {
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $this->loggerFactory->get('adv_audit');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Download audit in pdf.
   *
   * @param int $fid
   *   File id of current document.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Return the downloaded file.
   */
  public function download($fid) {
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    $file_uri = $file->getFileUri();

    if (empty($file_uri) || !file_exists($this->fileSystem->realpath($file_uri))) {
      $this->logger->notice('There are some troubles with audit file uri.');
      throw new NotFoundHttpException();
    }
    // Set headers for download.
    $headers['Content-Disposition'] = 'attachment; filename="' . $file->getFilename() . '"';

    return new BinaryFileResponse($file_uri, 200, $headers, FALSE);
  }

}
