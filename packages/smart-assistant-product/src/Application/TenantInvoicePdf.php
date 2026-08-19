<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Tenant\Invoice;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Throwable;

/**
 * Compact customer-facing invoice PDF for WhatsApp (tenant atelier only).
 */
final class TenantInvoicePdf
{
    public function render(Invoice $invoice, string $businessName): string
    {
        $invoice->loadMissing(['customer', 'branch', 'items.dress']);

        $kind = match ((string) $invoice->type) {
            Invoice::TYPE_SELL => 'بيع',
            Invoice::TYPE_TAILORING => 'تفصيل',
            default => 'تأجير',
        };

        $rows = '';
        foreach ($invoice->items as $item) {
            $name = (string) ($item->description ?: $item->dress?->name ?: 'صنف');
            $qty = (int) ($item->quantity ?: 1);
            $total = number_format((float) $item->total, 2);
            $rows .= '<tr><td>'.e($name).'</td><td style="text-align:center">'.$qty.'</td><td>'.$total.'</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="3">لا توجد بنود</td></tr>';
        }

        $dates = [];
        if ($invoice->rent_start_date) {
            $dates[] = 'الاستلام: '.$invoice->rent_start_date->toDateString();
        } elseif ($invoice->delivery_date) {
            $dates[] = 'التسليم: '.$invoice->delivery_date->toDateString();
        }
        if ($invoice->rent_end_date) {
            $dates[] = 'الإرجاع: '.$invoice->rent_end_date->toDateString();
        } elseif ($invoice->return_date) {
            $dates[] = 'الإرجاع: '.$invoice->return_date->toDateString();
        }

        $customer = (string) ($invoice->customer?->name ?: 'عميل');
        $branch = (string) ($invoice->branch?->name ?: '');
        $html = '<html dir="rtl"><head><meta charset="utf-8"></head><body style="font-family:dejavusans;font-size:12pt">'
            .'<h2 style="margin:0">'.e($businessName !== '' ? $businessName : 'الأتيليه').'</h2>'
            .'<p style="margin:6px 0 14px">فاتورة '.$kind.' رقم <strong>'.e((string) $invoice->invoice_number).'</strong></p>'
            .'<p>العميل: '.e($customer).($branch !== '' ? ' — الفرع: '.e($branch) : '').'</p>'
            .($dates !== [] ? '<p>'.e(implode(' — ', $dates)).'</p>' : '')
            .'<table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse">'
            .'<tr><th>البند</th><th>الكمية</th><th>الإجمالي</th></tr>'
            .$rows
            .'</table>'
            .'<p style="margin-top:14px">الإجمالي: <strong>'.number_format((float) $invoice->total, 2).'</strong>'
            .' — المدفوع: '.number_format((float) $invoice->paid_amount, 2)
            .' — المتبقي: '.number_format((float) $invoice->remaining_amount, 2).'</p>'
            .'<p style="color:#666;font-size:10pt">شكرًا لثقتك. هذه فاتورة صادرة من النظام.</p>'
            .'</body></html>';

        try {
            return $this->viaMpdf($html, (string) $invoice->invoice_number);
        } catch (Throwable) {
            return $this->viaDompdf($html);
        }
    }

    private function viaMpdf(string $html, string $title): string
    {
        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
            'default_font' => 'dejavusans',
            'default_font_size' => 11,
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoArabic' => true,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle('فاتورة '.$title);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function viaDompdf(string $html): string
    {
        $options = new \Dompdf\Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
