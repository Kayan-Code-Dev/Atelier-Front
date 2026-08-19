<?php

namespace App\Accounting;

use App\Models\Tenant\BankReconciliationMatch;
use Illuminate\Support\Carbon;

class BankMatchingEngine
{
    public const AUTO_MATCH_MIN_CONFIDENCE = 90;

    /**
     * @param  array{id: int, date: string, amount: float, description: ?string, reference: ?string}  $bank
     * @param  array{id: int, journal_entry_id: int, date: string, amount: float, description: ?string, reference: ?string}  $ledger
     * @return array{grade: string, confidence: int, reasons: list<string>}
     */
    public function score(array $bank, array $ledger): array
    {
        $reasons = [];
        $amountMatch = AccountingMoney::isZero(AccountingMoney::sub($bank['amount'], $ledger['amount']));
        if (! $amountMatch) {
            return [
                'grade' => BankReconciliationMatch::GRADE_UNMATCHED,
                'confidence' => 0,
                'reasons' => ['amount_mismatch'],
            ];
        }
        $reasons[] = 'amount';
        $confidence = 50;

        $dateDiff = (int) round(abs((float) Carbon::parse($bank['date'])->startOfDay()->diffInDays(Carbon::parse($ledger['date'])->startOfDay())));
        if ($dateDiff === 0) {
            $confidence += 40;
            $reasons[] = 'same_date';
        } elseif ($dateDiff <= 3) {
            $confidence += 15;
            $reasons[] = 'date_within_3';
        } elseif ($dateDiff <= 7) {
            $confidence += 5;
            $reasons[] = 'date_within_7';
        }

        $bankRef = $this->normalize($bank['reference'] ?? '');
        $ledgerRef = $this->normalize($ledger['reference'] ?? '');
        if ($bankRef !== '' && $bankRef === $ledgerRef) {
            $confidence += 20;
            $reasons[] = 'reference';
        } elseif ($this->operationOverlap($bank, $ledger)) {
            $confidence += 15;
            $reasons[] = 'operation_number';
        }

        $similarity = $this->descriptionSimilarity((string) ($bank['description'] ?? ''), (string) ($ledger['description'] ?? ''));
        if ($similarity >= 60) {
            $confidence += 10;
            $reasons[] = 'description';
        }

        $confidence = min(100, $confidence);
        $grade = $this->grade($confidence, $dateDiff, $amountMatch);

        return [
            'grade' => $grade,
            'confidence' => $confidence,
            'reasons' => $reasons,
        ];
    }

    public function shouldAutoMatch(string $grade, int $confidence): bool
    {
        return $grade === BankReconciliationMatch::GRADE_EXACT && $confidence >= self::AUTO_MATCH_MIN_CONFIDENCE;
    }

    private function grade(int $confidence, int $dateDiff, bool $amountMatch): string
    {
        if (! $amountMatch) {
            return BankReconciliationMatch::GRADE_UNMATCHED;
        }
        if ($dateDiff === 0) {
            return BankReconciliationMatch::GRADE_EXACT;
        }
        if ($confidence >= 70 && $dateDiff <= 3) {
            return BankReconciliationMatch::GRADE_LIKELY;
        }
        if ($dateDiff <= 7) {
            return BankReconciliationMatch::GRADE_POSSIBLE;
        }

        return BankReconciliationMatch::GRADE_UNMATCHED;
    }

    /**
     * @param  array{description: ?string, reference: ?string}  $bank
     * @param  array{description: ?string, reference: ?string}  $ledger
     */
    private function operationOverlap(array $bank, array $ledger): bool
    {
        $bankTokens = $this->operationTokens($bank['reference'] ?? '', $bank['description'] ?? '');
        $ledgerTokens = $this->operationTokens($ledger['reference'] ?? '', $ledger['description'] ?? '');

        return count(array_intersect($bankTokens, $ledgerTokens)) > 0;
    }

    /**
     * @return list<string>
     */
    private function operationTokens(string $reference, string $description): array
    {
        preg_match_all('/[A-Z]{0,4}\d{4,}/i', $reference.' '.$description, $matches);

        return array_values(array_unique(array_map(fn (string $token): string => strtoupper($token), $matches[0] ?? [])));
    }

    private function descriptionSimilarity(string $left, string $right): int
    {
        $left = $this->normalize($left);
        $right = $this->normalize($right);
        if ($left === '' || $right === '') {
            return 0;
        }
        similar_text($left, $right, $percent);

        return (int) round($percent);
    }

    private function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
