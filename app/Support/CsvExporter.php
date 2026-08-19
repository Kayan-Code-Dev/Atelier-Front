<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * @param  list<string>  $headers
     * @param  list<array<int|string,mixed>>  $rows
     */
    public static function download(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM so Excel on Windows reads Arabic correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    static function ($value) {
                        if ($value === null) {
                            return '';
                        }

                        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                    },
                    array_values($row),
                ));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int|string,mixed>>  $rows
     */
    public static function stream(string $filename, array $headers, array $rows): StreamedResponse
    {
        return self::download($filename, $headers, $rows);
    }
}
