<?php

namespace App\Accounting;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class BankStatementParser
{
    /**
     * @return list<array{date: string, description: string|null, reference: string|null, debit: float, credit: float, amount: float, raw: array<string, mixed>}>
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages(['file' => ['تعذر قراءة ملف كشف البنك.']]);
        }

        $rows = match ($extension) {
            'csv', 'txt' => $this->parseCsv($path),
            'xlsx' => $this->parseXlsx($path),
            default => throw ValidationException::withMessages(['file' => ['يُسمح فقط بملفات CSV أو XLSX.']]),
        };

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => ['الملف لا يحتوي على حركات صالحة.']]);
        }

        return $rows;
    }

    /**
     * @return list<array{date: string, description: string|null, reference: string|null, debit: float, credit: float, amount: float, raw: array<string, mixed>}>
     */
    public function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => ['تعذر فتح ملف CSV.']]);
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header) || $header === []) {
                throw ValidationException::withMessages(['file' => ['ملف CSV بدون عناوين أعمدة.']]);
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
            $map = $this->mapHeaders($header);
            $this->assertRequired($map);

            $rows = [];
            while (($data = fgetcsv($handle)) !== false) {
                if ($this->isEmptyRow($data)) {
                    continue;
                }
                $assoc = [];
                foreach ($header as $index => $name) {
                    $assoc[(string) $name] = $data[$index] ?? null;
                }
                $parsed = $this->normalizeRow($assoc, $map);
                if ($parsed !== null) {
                    $rows[] = $parsed;
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<array{date: string, description: string|null, reference: string|null, debit: float, credit: float, amount: float, raw: array<string, mixed>}>
     */
    public function parseXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => ['تعذر قراءة ملف Excel.']]);
        }

        try {
            $shared = $this->xlsxSharedStrings($zip->getFromName('xl/sharedStrings.xml') ?: '');
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (! is_string($sheetXml) || $sheetXml === '') {
                throw ValidationException::withMessages(['file' => ['ورقة Excel الأولى فارغة.']]);
            }

            $grid = $this->xlsxSheetGrid($sheetXml, $shared);
            if ($grid === []) {
                throw ValidationException::withMessages(['file' => ['ملف Excel بدون صفوف.']]);
            }

            $header = array_shift($grid);
            $map = $this->mapHeaders($header);
            $this->assertRequired($map);

            $rows = [];
            foreach ($grid as $data) {
                if ($this->isEmptyRow($data)) {
                    continue;
                }
                $assoc = [];
                foreach ($header as $index => $name) {
                    $assoc[(string) $name] = $data[$index] ?? null;
                }
                $parsed = $this->normalizeRow($assoc, $map);
                if ($parsed !== null) {
                    $rows[] = $parsed;
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    private function mapHeaders(array $header): array
    {
        $aliases = [
            'date' => ['date', 'التاريخ', 'txn_date', 'transaction_date', 'valuedate', 'value_date'],
            'description' => ['description', 'desc', 'narration', 'البيان', 'الوصف', 'details'],
            'reference' => ['reference', 'ref', 'المرجع', 'رقم العملية', 'operation', 'txn_id', 'transaction_id'],
            'debit' => ['debit', 'مدين', 'withdrawal', 'withdrawals', 'dr'],
            'credit' => ['credit', 'دائن', 'deposit', 'deposits', 'cr'],
            'amount' => ['amount', 'المبلغ', 'value', 'net'],
        ];

        $map = [];
        foreach ($header as $index => $label) {
            $normalized = $this->normalizeHeader((string) $label);
            foreach ($aliases as $key => $names) {
                if (in_array($normalized, $names, true)) {
                    $map[$key] = $index;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $map
     */
    private function assertRequired(array $map): void
    {
        if (! isset($map['date'])) {
            throw ValidationException::withMessages(['file' => ['عمود التاريخ مطلوب في ملف الكشف.']]);
        }
        if (! isset($map['amount']) && ! isset($map['debit']) && ! isset($map['credit'])) {
            throw ValidationException::withMessages(['file' => ['يجب توفير عمود المبلغ أو المدين/الدائن.']]);
        }
    }

    /**
     * @param  array<int|string, mixed>  $assoc
     * @param  array<string, int>  $map
     * @return array{date: string, description: string|null, reference: string|null, debit: float, credit: float, amount: float, raw: array<string, mixed>}|null
     */
    private function normalizeRow(array $assoc, array $map): ?array
    {
        $values = array_values($assoc);
        $dateRaw = (string) ($values[$map['date']] ?? '');
        $date = $this->parseDate($dateRaw);
        if ($date === null) {
            throw ValidationException::withMessages(['file' => ['تاريخ غير صالح: '.$dateRaw]]);
        }

        $debit = $this->toMoney($values[$map['debit'] ?? -1] ?? 0);
        $credit = $this->toMoney($values[$map['credit'] ?? -1] ?? 0);
        $amount = isset($map['amount'])
            ? $this->toMoney($values[$map['amount']] ?? 0)
            : round($credit - $debit, 2);

        if (abs($amount) < 0.005 && $debit < 0.005 && $credit < 0.005) {
            return null;
        }

        if (abs($amount) >= 0.005 && $debit < 0.005 && $credit < 0.005) {
            if ($amount >= 0) {
                $credit = $amount;
            } else {
                $debit = abs($amount);
            }
        }

        $description = trim((string) ($values[$map['description'] ?? -1] ?? ''));
        $reference = trim((string) ($values[$map['reference'] ?? -1] ?? ''));

        return [
            'date' => $date,
            'description' => $description !== '' ? mb_substr($description, 0, 255) : null,
            'reference' => $reference !== '' ? mb_substr($reference, 0, 120) : null,
            'debit' => $debit,
            'credit' => $credit,
            'amount' => $amount,
            'raw' => [
                'date' => $date,
                'description' => $description,
                'reference' => $reference,
                'debit' => $debit,
                'credit' => $credit,
                'amount' => $amount,
            ],
        ];
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $excel = (int) $value;
            if ($excel > 20000 && $excel < 80000) {
                return Carbon::create(1899, 12, 30)->addDays($excel)->toDateString();
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y', 'm/d/Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false && $parsed->format($format) === $value) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function toMoney(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $normalized = str_replace([',', ' ', '٬'], ['', '', ''], (string) $value);
        if (! is_numeric($normalized)) {
            return 0.0;
        }

        return round((float) $normalized, 2);
    }

    private function normalizeHeader(string $label): string
    {
        $label = strtolower(trim($label));
        $label = str_replace(['_', '-'], ' ', $label);

        return preg_replace('/\s+/', ' ', $label) ?? $label;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function xlsxSharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $strings = [];
        if (preg_match_all('/<si\b[^>]*>(.*?)<\/si>/s', $xml, $matches) === false) {
            return [];
        }
        foreach ($matches[1] as $block) {
            preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $block, $texts);
            $strings[] = html_entity_decode(implode('', $texts[1] ?? []), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        return $strings;
    }

    /**
     * @param  list<string>  $shared
     * @return list<list<string>>
     */
    private function xlsxSheetGrid(string $xml, array $shared): array
    {
        $grid = [];
        if (preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s', $xml, $rows) === false) {
            return [];
        }

        foreach ($rows[1] as $rowXml) {
            $cells = [];
            if (preg_match_all('/<c\b([^>]*)>(?:<v>(.*?)<\/v>)?/s', $rowXml, $cellMatches, PREG_SET_ORDER) === false) {
                continue;
            }
            foreach ($cellMatches as $cell) {
                $attrs = $cell[1] ?? '';
                $value = html_entity_decode((string) ($cell[2] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
                $col = 0;
                if (preg_match('/r="([A-Z]+)\d+"/i', $attrs, $ref)) {
                    $col = $this->columnIndex($ref[1]);
                }
                if (str_contains($attrs, 't="s"') && is_numeric($value)) {
                    $value = $shared[(int) $value] ?? $value;
                }
                $cells[$col] = $value;
            }
            if ($cells === []) {
                continue;
            }
            ksort($cells);
            $max = max(array_keys($cells));
            $line = [];
            for ($i = 0; $i <= $max; $i++) {
                $line[] = $cells[$i] ?? '';
            }
            $grid[] = $line;
        }

        return $grid;
    }

    private function columnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }
}
