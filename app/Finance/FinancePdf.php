<?php

declare(strict_types=1);

namespace App\Finance;

use Dompdf\Dompdf;
use Dompdf\Options;

final class FinancePdf
{
    public static function invoice(array $document): string
    {
        return self::render('invoice.php', ['document' => $document], $document['number']);
    }

    public static function report(array $report): string
    {
        return self::render('financial-report.php', ['report' => $report], 'Rapport financier');
    }

    private static function render(string $template, array $data, string $footer): string
    {
        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setIsFontSubsettingEnabled(true);
        $options->setDefaultFont('DejaVu Sans');

        ob_start();
        try {
            require dirname(__DIR__) . '/Views/pdf/' . $template;
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(45, 803, $footer . '  |  Page {PAGE_NUM}/{PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.32, 0.28, 0.32]);

        return $dompdf->output();
    }
}
