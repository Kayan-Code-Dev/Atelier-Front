<?php

namespace App\Support\Reports;

use App\Enums\ReportExportFormat;
use App\Support\CsvExporter;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReportExporter
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>|array<string, mixed>>  $rows
     */
    public static function download(
        ReportExportFormat $format,
        string $basename,
        string $title,
        array $headers,
        array $rows,
        ?array $meta = null,
    ): StreamedResponse|Response {
        $filename = $basename.'.'.$format->value;
        $normalizedRows = self::normalizeRows($headers, $rows);

        return match ($format) {
            ReportExportFormat::CSV => CsvExporter::download($filename, $headers, $normalizedRows),
            ReportExportFormat::XLSX => self::xlsx($filename, $title, $headers, $normalizedRows, $meta),
            ReportExportFormat::PDF => self::pdf($filename, $title, $headers, $normalizedRows, $meta),
        };
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>|array<string, mixed>>  $rows
     * @return list<list<string|int|float|null>>
     */
    private static function normalizeRows(array $headers, array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_is_list($row)) {
                $normalized[] = array_map(
                    static fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
                    array_values($row),
                );

                continue;
            }

            $ordered = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? null;
                $ordered[] = is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $normalized[] = $ordered;
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     * @param  array<string, mixed>|null  $meta
     */
    private static function xlsx(
        string $filename,
        string $title,
        array $headers,
        array $rows,
        ?array $meta,
    ): StreamedResponse {
        return response()->streamDownload(function () use ($title, $headers, $rows, $meta): void {
            $options = new Options;
            $writer = new XlsxWriter($options);
            $writer->openToFile('php://output');

            // Sheet RTL so Arabic columns/read order match Excel expectations.
            $sheetView = new SheetView;
            $sheetView->setRightToLeft(true);
            $writer->getCurrentSheet()->setSheetView($sheetView);

            $titleStyle = (new Style)
                ->setFontName('Arial')
                ->setFontBold()
                ->setFontSize(14)
                ->setFontColor(Color::rgb(26, 58, 109))
                ->setCellAlignment(CellAlignment::RIGHT);

            $metaStyle = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::rgb(85, 85, 85))
                ->setCellAlignment(CellAlignment::RIGHT);

            $headerStyle = (new Style)
                ->setFontName('Arial')
                ->setFontBold()
                ->setFontSize(11)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(26, 58, 109))
                ->setCellAlignment(CellAlignment::RIGHT);

            $writer->addRow(Row::fromValues([$title], $titleStyle));

            if (is_array($meta) && $meta !== []) {
                foreach ($meta as $label => $value) {
                    $text = (string) $label.': '.(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE));
                    $writer->addRow(Row::fromValues([$text], $metaStyle));
                }
            }

            $writer->addRow(new Row([Cell::fromValue('')]));

            $headerCells = array_map(
                static fn (string $header): Cell => Cell::fromValue($header, $headerStyle),
                $headers,
            );
            $writer->addRow(new Row($headerCells));

            foreach ($rows as $index => $row) {
                $rowStyle = (new Style)
                    ->setFontName('Arial')
                    ->setFontSize(10)
                    ->setCellAlignment(CellAlignment::RIGHT);

                if ($index % 2 === 1) {
                    $rowStyle->setBackgroundColor(Color::rgb(248, 250, 252));
                }

                $cells = array_map(
                    static function ($value) use ($rowStyle): Cell {
                        if (is_int($value) || is_float($value)) {
                            return Cell::fromValue($value, $rowStyle);
                        }

                        return Cell::fromValue($value === null ? '' : (string) $value, $rowStyle);
                    },
                    $row,
                );
                $writer->addRow(new Row($cells));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     * @param  array<string, mixed>|null  $meta
     */
    private static function pdf(
        string $filename,
        string $title,
        array $headers,
        array $rows,
        ?array $meta,
    ): Response {
        $generatedAt = now()->timezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i');

        $html = view('reports.export-table', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'meta' => $meta ?? [],
            'generatedAt' => $generatedAt,
            'rowCount' => count($rows),
        ])->render();

        try {
            $tempDir = storage_path('app/mpdf-temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            @chmod($tempDir, 0775);

            $defaultConfig = (new ConfigVariables)->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            $defaultFontConfig = (new FontVariables)->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 12,
                'margin_bottom' => 12,
                'default_font' => 'dejavusans',
                'default_font_size' => 10,
                'directionality' => 'rtl',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'autoArabic' => true,
                'fontDir' => $fontDirs,
                'fontdata' => $fontData,
                'tempDir' => $tempDir,
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->SetTitle($title);
            $mpdf->SetAuthor('DressnMore');
            $mpdf->WriteHTML($html);

            return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            // Dompdf is a last resort only — Arabic shaping is weaker than mPDF.
            return self::pdfViaDompdfFallback($filename, $html);
        }
    }

    private static function pdfViaDompdfFallback(string $filename, string $html): Response
    {
        $options = new \Dompdf\Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->setChroot(base_path());

        // Prefer embedded DejaVu paths shipped with Dompdf for Arabic glyphs.
        $dompdfFontDir = base_path('vendor/dompdf/dompdf/lib/fonts');
        if (is_dir($dompdfFontDir)) {
            $options->setFontDir($dompdfFontDir);
            $options->setFontCache($dompdfFontDir);
        }

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
