<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingAuditService;
use App\Accounting\SchemaHasAccountingPeriods;
use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\FixedAssetDepreciationEntry;
use App\Models\Tenant\FixedAssetDepreciationRun;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalEntryLine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AccountingPeriodService
{
    /** @var array<int, string> */
    private const MONTH_NAMES = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
        7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    public function __construct(
        private readonly AccountingAuditService $audit,
        private readonly AccountingControlService $controls,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function list(?int $year = null): array
    {
        $this->ensureYears($year);

        $query = AccountingPeriod::query()
            ->with(['closer:id,name', 'reopener:id,name'])
            ->orderByDesc('starts_on');

        if ($year) {
            $query->where('year', $year);
        }

        return $query->get()->map(fn (AccountingPeriod $period): array => $this->present($period))->all();
    }

    public function findOrFail(int $id): AccountingPeriod
    {
        return AccountingPeriod::query()->findOrFail($id);
    }

    public function current(): ?AccountingPeriod
    {
        $this->ensureYears();

        return AccountingPeriod::query()
            ->whereDate('starts_on', '<=', now()->toDateString())
            ->whereDate('ends_on', '>=', now()->toDateString())
            ->first();
    }

    /**
     * Close is a control boundary only. Journals and ledger lines are never mutated.
     *
     * @return array<string, mixed>
     */
    public function close(AccountingPeriod $period, ?int $actorId, bool $confirm = false): array
    {
        if ($period->blocksPosting()) {
            throw ValidationException::withMessages([
                'period' => ['هذه الفترة مغلقة أو مقفلة مسبقاً.'],
            ]);
        }

        $validation = $this->validateClose($period);
        if (! $validation['can_close']) {
            $failed = collect($validation['checks'])
                ->filter(fn (array $check): bool => ! $check['ok'])
                ->map(fn (array $check): string => $check['label'].($check['detail'] ? ': '.$check['detail'] : ''))
                ->values()
                ->all();

            throw ValidationException::withMessages([
                'period' => array_merge(['لا يمكن إغلاق الفترة قبل معالجة الفحوصات الفاشلة.'], $failed),
            ]);
        }

        if (! $confirm) {
            throw ValidationException::withMessages([
                'confirm' => ['هل أنت متأكد من إغلاق '.$period->name.'؟ أرسل confirm=true للمتابعة.'],
            ]);
        }

        $journalCount = $this->periodJournals($period)->count();

        $period->update([
            'status' => AccountingPeriod::STATUS_CLOSED,
            'is_closed' => true,
            'closed_by' => $actorId,
            'closed_at' => now(),
        ]);

        $this->audit->record($actorId, 'period_closed', 'accounting_period', $period->id, [
            'name' => $period->name,
            'starts_on' => $period->starts_on?->toDateString(),
            'ends_on' => $period->ends_on?->toDateString(),
            'journal_count_unchanged' => $journalCount,
        ]);

        return $this->present($period->fresh(['closer', 'reopener']));
    }

    /**
     * @return array<string, mixed>
     */
    public function reopen(AccountingPeriod $period, ?int $actorId, string $reason): array
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 8) {
            throw ValidationException::withMessages([
                'reason' => ['سبب إعادة الفتح مطلوب (8 أحرف على الأقل).'],
            ]);
        }

        if ($period->isOpen()) {
            throw ValidationException::withMessages([
                'period' => ['الفترة مفتوحة بالفعل.'],
            ]);
        }

        $journalCount = $this->periodJournals($period)->count();

        $period->update([
            'status' => AccountingPeriod::STATUS_OPEN,
            'is_closed' => false,
            'reopen_reason' => $reason,
            'reopened_by' => $actorId,
            'reopened_at' => now(),
            'locked_by' => null,
            'locked_at' => null,
        ]);

        $this->audit->record($actorId, 'period_reopened', 'accounting_period', $period->id, [
            'name' => $period->name,
            'reason' => $reason,
            'reopened_by' => $actorId,
            'reopened_at' => now()->toIso8601String(),
            'journal_count_unchanged' => $journalCount,
        ]);

        return $this->present($period->fresh(['closer', 'reopener']));
    }

    /**
     * @return array<string, mixed>
     */
    public function lock(AccountingPeriod $period, ?int $actorId): array
    {
        if ($period->status === AccountingPeriod::STATUS_LOCKED) {
            throw ValidationException::withMessages([
                'period' => ['هذه الفترة مقفلة مسبقاً.'],
            ]);
        }

        if ($period->isOpen()) {
            throw ValidationException::withMessages([
                'period' => ['أقفل الفترة بعد إغلاقها أولاً.'],
            ]);
        }

        $period->update([
            'status' => AccountingPeriod::STATUS_LOCKED,
            'is_closed' => true,
            'locked_by' => $actorId,
            'locked_at' => now(),
        ]);

        $this->audit->record($actorId, 'period_locked', 'accounting_period', $period->id, [
            'name' => $period->name,
        ]);

        return $this->present($period->fresh(['closer', 'reopener']));
    }

    public function assertOpen(string $date): void
    {
        if (! SchemaHasAccountingPeriods::exists()) {
            return;
        }

        $period = AccountingPeriod::query()
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->first();

        if ($period instanceof AccountingPeriod && $period->blocksPosting()) {
            throw ValidationException::withMessages([
                'entry_date' => [
                    'لا يمكن إنشاء أو تعديل أو ترحيل قيد في فترة محاسبية مغلقة ('.$period->name.'). أنشئ قيد تصحيح في الفترة المفتوحة الحالية.',
                ],
            ]);
        }
    }

    /**
     * @return array{can_close: bool, checks: list<array<string, mixed>>}
     */
    public function validateClose(AccountingPeriod $period): array
    {
        $from = $period->starts_on?->toDateString() ?? '';
        $to = $period->ends_on?->toDateString() ?? '';
        $periodKey = sprintf('%04d-%02d', (int) $period->year, (int) ($period->month ?: 1));

        $unbalanced = $this->periodJournals($period)
            ->where(function ($query): void {
                $query->where('is_balanced', false)
                    ->orWhereColumn('total_debit', '!=', 'total_credit');
            })
            ->count();

        $pendingStatuses = [
            JournalEntry::STATUS_DRAFT,
            JournalEntry::STATUS_PENDING_APPROVAL,
            JournalEntry::STATUS_APPROVED,
        ];
        $pendingJournals = $this->periodJournals($period)->whereIn('status', $pendingStatuses)->count();

        $pendingDepreciation = Schema::connection('tenant')->hasTable('fixed_asset_depreciation_runs')
            ? FixedAssetDepreciationRun::query()
                ->where('period', $periodKey)
                ->where('status', FixedAssetDepreciationRun::STATUS_PENDING)
                ->count()
            : 0;

        $pendingDepreciationEntries = Schema::connection('tenant')->hasTable('fixed_asset_depreciation_entries')
            ? FixedAssetDepreciationEntry::query()
                ->where('period', $periodKey)
                ->where('status', FixedAssetDepreciationEntry::STATUS_PENDING)
                ->count()
            : 0;

        $orphanLines = JournalEntryLine::query()
            ->where(function ($query): void {
                $query->whereDoesntHave('journalEntry')
                    ->orWhereNull('account_id')
                    ->orWhereDoesntHave('account');
            })
            ->count();

        $health = $this->controls->checks(['date_from' => $from, 'date_to' => $to, 'date' => $to]);

        $depreciationOk = $pendingDepreciation === 0 && $pendingDepreciationEntries === 0;

        $checks = [
            $this->check('journals_balanced', 'جميع القيود متوازنة', $unbalanced === 0, $unbalanced === 0 ? null : "{$unbalanced} قيد غير متوازن"),
            $this->check('no_pending_journals', 'لا توجد قيود معلّقة', $pendingJournals === 0, $pendingJournals === 0 ? null : "{$pendingJournals} قيد غير مرحّل"),
            $this->check('depreciation_posted', 'الإهلاك مستحق/مرحّل', $depreciationOk, $depreciationOk ? null : 'يوجد إهلاك معلّق أو غير مرحّل لهذه الفترة'),
            $this->check('no_orphan_lines', 'لا توجد سطور يتيمة', $orphanLines === 0, $orphanLines === 0 ? null : "{$orphanLines} سطر يتيم"),
            $this->check('ledger_balanced', 'دفتر الأستاذ متوازن', (bool) ($health['ledger_balanced']['ok'] ?? false), $health['ledger_balanced']['detail'] ?? null),
            $this->check('no_ledger_errors', 'لا توجد أخطاء Ledger', (bool) ($health['no_orphan_journal_lines']['ok'] ?? false) && (bool) ($health['debits_equal_credits']['ok'] ?? false)),
        ];

        $canClose = collect($checks)->every(fn (array $check): bool => $check['ok'] === true);

        return [
            'can_close' => $canClose,
            'period' => $this->present($period),
            'checks' => $checks,
        ];
    }

    public function ensureYears(?int $year = null): void
    {
        if (! SchemaHasAccountingPeriods::exists()) {
            return;
        }

        $years = array_unique([
            (int) now()->year,
            (int) now()->subYear()->year,
            $year ? (int) $year : (int) now()->year,
        ]);

        foreach ($years as $targetYear) {
            for ($month = 1; $month <= 12; $month++) {
                $start = CarbonImmutable::create($targetYear, $month, 1);
                if (! $start) {
                    continue;
                }
                AccountingPeriod::query()->firstOrCreate(
                    [
                        'year' => $targetYear,
                        'starts_on' => $start->toDateString(),
                        'ends_on' => $start->endOfMonth()->toDateString(),
                    ],
                    [
                        'month' => $month,
                        'name' => (self::MONTH_NAMES[$month] ?? 'فترة').' '.$targetYear,
                        'status' => AccountingPeriod::STATUS_OPEN,
                        'is_closed' => false,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function present(AccountingPeriod $period): array
    {
        return [
            'id' => $period->id,
            'year' => $period->year,
            'month' => $period->month,
            'name' => $period->name,
            'starts_on' => $period->starts_on?->toDateString(),
            'ends_on' => $period->ends_on?->toDateString(),
            'status' => $period->status ?: ($period->is_closed ? AccountingPeriod::STATUS_CLOSED : AccountingPeriod::STATUS_OPEN),
            'is_closed' => $period->blocksPosting(),
            'closed_by' => $period->closer?->name,
            'closed_at' => $period->closed_at?->toIso8601String(),
            'reopen_reason' => $period->reopen_reason,
            'reopened_by' => $period->reopener?->name,
            'reopened_at' => $period->reopened_at?->toIso8601String(),
        ];
    }

    private function periodJournals(AccountingPeriod $period)
    {
        return JournalEntry::query()
            ->whereDate('entry_date', '>=', $period->starts_on)
            ->whereDate('entry_date', '<=', $period->ends_on);
    }

    /**
     * @return array{key: string, label: string, ok: bool, detail: string|null}
     */
    private function check(string $key, string $label, bool $ok, ?string $detail = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ok' => $ok,
            'detail' => $ok ? null : $detail,
        ];
    }
}
