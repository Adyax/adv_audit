<?php

namespace Drupal\adv_audit\Controller;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\Response;

/**
 * The class of the Pdf generation' controller.
 */
class AdvAuditPdfController {

  /**
   * Public function view.
   */
  public function printPdf($adv_audit) {
    /**
     * Get rendered report
     */
    $entity_type = 'adv_audit';
    $view_mode = 'pdf';
    $entity_report = \Drupal::entityTypeManager()->getStorage($entity_type)->load($adv_audit->id->value);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $build = $view_builder->view($entity_report, $view_mode);
    $renderer = render($build);

    // Get the name of current report.
    $report_name = $adv_audit->name->value;

    $headers = [
      'Content-Type: application/pdf',
      'Charset: utf-8',
    ];
    $config = [
      'mode' => 'utf-8',
      'tempDir' => DRUPAL_ROOT . '/sites/default/files/tmp',
    ];

    $mpdf = new Mpdf($config);
    $mpdf->SetBasePath(drupal_get_path('module', 'adv_audit'));
    /**
     * Start a pdf-header and pdf-footer
     */
    $mpdf->SetTitle($report_name);
    $mpdf->SetHTMLHeader('
      <div style="text-align: right; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px;">
        ' . t('Advanced Audit') . '
      </div>
    ');
    $footer_report_name = stristr($report_name, ' by', TRUE);
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
    $mpdf->WriteHTML($renderer, 2);
    $mpdf->Output('adv-audit-report-' . $adv_audit->id->value . '.pdf', Destination::INLINE);

    return new Response($renderer, 200, $headers);
  }

}
