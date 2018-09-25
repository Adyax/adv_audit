<?php

namespace Drupal\adv_audit\Controller;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\Response;

/**
 * The class of the Pdf generation' controller.
 */
class AuditPdfController {

  /**
   * Public function view.
   */
  public function printPdf($adv_audit) {
    /**
     * Get rendered report
     */
    $date_report = date('Y-m-d\TH-i-sO', time());
    $entity_type = 'adv_audit';
    $view_mode = 'pdf';

    $entity_report = \Drupal::entityTypeManager()->getStorage($entity_type)->load($adv_audit->id->value);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    // Reset renderer cache
    $view_builder->resetCache([$entity_report]);

    $build = $view_builder->view($entity_report, $view_mode);
    $renderer = render($build);

    $headers = [
      'Content-Type: application/pdf',
      'Charset: utf-8',
    ];
    $config = [
      'mode' => 'utf-8',
      'tempDir' => DRUPAL_ROOT . '/sites/default/files/tmp',
      'setAutoTopMargin' => 'stretch',
      'setAutoBottomMargin' => 'stretch',
      'autoMarginPadding' => 10,
    ];
    $file_name  = 'adv-audit-report-' . $date_report . '.pdf';

    $mpdf = new Mpdf($config);
    $mpdf->SetBasePath(drupal_get_path('module', 'adv_audit'));
    $mpdf->SetAuthor(t('Adyax Advanced Audit module'));

    /**
     * Start a pdf-header and pdf-footer
     */
    $mpdf->SetTitle($adv_audit->name->value);
    $mpdf->SetHTMLHeader('
      <div style="text-align: right; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px;">
        ' . t('Advanced Audit') . '
      </div>
    ');
    $footer_report_name = stristr($adv_audit->name->value, ' by', TRUE);
    $mpdf->SetHTMLFooter('
      <table width="100%" style="border-top: 1px solid #ccc; padding-top:10px; font-family: serif; font-size: 8pt; font-weight: bold; font-style: italic; color: #000000;">
        <tr>
          <td width="60%">' . $footer_report_name . '</td>
          <td width="40%" align="right" style="font-size: 10pt;">{PAGENO} / {nbpg}</td>
        </tr>
      </table>
    ');
    /**
     * End a pdf-header and pdf-footer
     */

    $stylesheet = file_get_contents(drupal_get_path('module', 'adv_audit') . '/css/view_pdf_results.css');
    $mpdf->WriteHTML($stylesheet, 1);
    $mpdf->WriteHTML($renderer);
    $mpdf->Output($file_name, Destination::INLINE);

    return new Response($renderer, 200, $headers);
  }

}
