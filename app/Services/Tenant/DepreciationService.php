<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingMoney;
use App\Models\Tenant\FixedAsset;
use App\Models\Tenant\FixedAssetDepreciationEntry;
use App\Models\Tenant\FixedAssetDepreciationRun;
use App\Models\Tenant\FixedAssetDepreciationSchedule;
use App\Models\Tenant\FixedAssetTransaction;
use App\Models\Tenant\JournalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepreciationService
{
    public function __construct(
        private readonly JournalEntryService $journals,
        private readonly AccountingAuditService $audit,
    ) {}

    public function rebuildSchedule(FixedAsset $asset): void
    {
        FixedAssetDepreciationSchedule::query()
            ->where('fixed_asset_id', $asset->id)
            ->where('status', FixedAssetDepreciationSchedule::STATUS_PENDING)
            ->delete();

        $months = $this->lifeInMonths($asset);
        $depreciable = AccountingMoney::sub($asset->acquisition_cost, $asset->salvage_value);
        if ($months <= 0 || AccountingMoney::cmp($depreciable, '0') <= 0) {
            return;
        }

        $monthly = AccountingMoney::div($depreciable, $months);
        $allocated = AccountingMoney::zero();
        $start = Carbon::parse($asset->placed_in_service_date)->startOfMonth();

        for ($index = 0; $index < $months; $index++) {
            $period = $start->copy()->addMonths($index)->format('Y-m');
            $existing = FixedAssetDepreciationSchedule::query()
                ->where('fixed_asset_id', $asset->id)
                ->where('period', $period)
                ->first();
            if ($existing && $existing->status !== FixedAssetDepreciationSchedule::STATUS_PENDING) {
                $allocated = AccountingMoney::add($allocated, $existing->amount);

                continue;
            }

            $amount = $index === $months - 1
                ? AccountingMoney::sub($depreciable, $allocated)
                : $monthly;
            $allocated = AccountingMoney::add($allocated, $amount);
            $book = AccountingMoney::sub($asset->acquisition_cost, $allocated);

            FixedAssetDepreciationSchedule::query()->updateOrCreate(
                ['fixed_asset_id' => $asset->id, 'period' => $period],
                [
                    'sequence' => $index + 1,
                    'amount' => $amount,
                    'accumulated' => $allocated,
                    'book_value' => $book,
                    'status' => FixedAssetDepreciationSchedule::STATUS_PENDING,
                ]
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $period, ?int $branchId): array
    {
        $this->assertPeriod($period);
        $existing = $this->existingPostedRun($period, $branchId);
        $assets = $this->eligibleAssets($period, $branchId);

        return [
            'period' => $period,
            'branch_id' => $branchId,
            'already_posted' => $existing !== null,
            'run_id' => $existing?->id,
            'assets_count' => $assets->count(),
            'total_depreciation' => AccountingMoney::toFloat($assets->sum(fn (array $row) => $row['amount'])),
            'assets' => $assets->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function post(string $period, ?int $branchId, ?int $actorId): array
    {
        $this->assertPeriod($period);
        $key = $this->runKey($period, $branchId);
        $existing = FixedAssetDepreciationRun::query()->where('idempotency_key', $key)->first();
        if ($existing instanceof FixedAssetDepreciationRun && $existing->status === FixedAssetDepreciationRun::STATUS_POSTED) {
            throw ValidationException::withMessages(['period' => ['تم ترحيل إهلاك هذه الفترة مسبقاً.']]);
        }

        $preview = $this->preview($period, $branchId);
        if ($preview['assets_count'] === 0) {
            throw ValidationException::withMessages(['period' => ['لا توجد أصول مستحقة للإهلاك في هذه الفترة.']]);
        }

        return DB::connection('tenant')->transaction(function () use ($period, $branchId, $actorId, $key, $preview): array {
            $run = FixedAssetDepreciationRun::query()->create([
                'period' => $period,
                'branch_id' => $branchId,
                'status' => FixedAssetDepreciationRun::STATUS_PENDING,
                'assets_count' => $preview['assets_count'],
                'total_amount' => AccountingMoney::of($preview['total_depreciation']),
                'idempotency_key' => $key,
                'created_by' => $actorId,
            ]);

            $linesByAccount = [];
            foreach ($preview['assets'] as $row) {
                $entry = FixedAssetDepreciationEntry::query()->create([
                    'run_id' => $run->id,
                    'fixed_asset_id' => $row['id'],
                    'schedule_id' => $row['schedule_id'],
                    'period' => $period,
                    'amount' => AccountingMoney::of($row['amount']),
                    'status' => FixedAssetDepreciationEntry::STATUS_PENDING,
                    'idempotency_key' => $row['id'].':'.$period,
                ]);

                $expenseId = (int) $row['depreciation_expense_account_id'];
                $accumId = (int) $row['accumulated_depreciation_account_id'];
                $linesByAccount[$expenseId] = AccountingMoney::add($linesByAccount[$expenseId] ?? 0, $row['amount']);
                $linesByAccount['cr:'.$accumId] = AccountingMoney::add($linesByAccount['cr:'.$accumId] ?? 0, $row['amount']);

                unset($entry);
            }

            $lines = [];
            foreach ($linesByAccount as $keyAccount => $amount) {
                if (str_starts_with((string) $keyAccount, 'cr:')) {
                    $lines[] = [
                        'account_id' => (int) substr((string) $keyAccount, 3),
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => 'إهلاك '.$period,
                    ];
                } else {
                    $lines[] = [
                        'account_id' => (int) $keyAccount,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => 'إهلاك '.$period,
                    ];
                }
            }

            $entryDate = Carbon::createFromFormat('Y-m', $period)?->endOfMonth()->toDateString() ?? now()->toDateString();
            $journal = $this->journals->createFromSource([
                'entry_date' => $entryDate,
                'source_type' => JournalEntry::SOURCE_DEPRECIATION,
                'source_id' => $run->id,
                'reference_number' => 'DEP-'.$period,
                'description' => 'إهلاك أصول ثابتة للفترة '.$period,
                'branch_id' => $branchId,
            ], $lines, $actorId);

            if (! $journal->is_balanced) {
                throw ValidationException::withMessages(['journal' => ['قيد الإهلاك غير متوازن.']]);
            }

            $now = now();
            $run->forceFill([
                'status' => FixedAssetDepreciationRun::STATUS_POSTED,
                'journal_entry_id' => $journal->id,
                'posted_by' => $actorId,
                'posted_at' => $now,
            ])->save();

            FixedAssetDepreciationEntry::query()
                ->where('run_id', $run->id)
                ->update([
                    'status' => FixedAssetDepreciationEntry::STATUS_POSTED,
                    'journal_entry_id' => $journal->id,
                    'posted_by' => $actorId,
                    'posted_at' => $now,
                ]);

            foreach ($preview['assets'] as $row) {
                FixedAssetDepreciationSchedule::query()
                    ->where('id', $row['schedule_id'])
                    ->update(['status' => FixedAssetDepreciationSchedule::STATUS_POSTED]);

                $asset = FixedAsset::query()->find($row['id']);
                if ($asset instanceof FixedAsset) {
                    FixedAssetTransaction::query()->create([
                        'fixed_asset_id' => $asset->id,
                        'type' => 'depreciation',
                        'occurred_at' => $entryDate,
                        'amount' => AccountingMoney::of($row['amount']),
                        'journal_entry_id' => $journal->id,
                        'payload' => ['period' => $period, 'run_id' => $run->id],
                        'created_by' => $actorId,
                    ]);
                    $this->refreshAssetStatus($asset);
                }
            }

            $this->audit->record($actorId, 'posted', 'depreciation_run', $run->id, [
                'period' => $period,
                'journal_entry_id' => $journal->id,
                'assets_count' => $preview['assets_count'],
            ]);

            return [
                'period' => $period,
                'branch_id' => $branchId,
                'already_posted' => true,
                'run_id' => $run->id,
                'journal_entry_id' => $journal->id,
                'assets_count' => $preview['assets_count'],
                'total_depreciation' => $preview['total_depreciation'],
                'assets' => $preview['assets'],
            ];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function scheduleFor(FixedAsset $asset): array
    {
        return $asset->schedules()->orderBy('sequence')->get()->map(function (FixedAssetDepreciationSchedule $row) {
            return [
                'id' => $row->id,
                'period' => $row->period,
                'amount' => AccountingMoney::toFloat($row->amount),
                'accumulated' => AccountingMoney::toFloat($row->accumulated),
                'book_value' => AccountingMoney::toFloat($row->book_value),
                'status' => $row->status,
            ];
        })->all();
    }

    private function eligibleAssets(string $period, ?int $branchId)
    {
        $query = FixedAssetDepreciationSchedule::query()
            ->with('asset')
            ->where('period', $period)
            ->where('status', FixedAssetDepreciationSchedule::STATUS_PENDING)
            ->whereHas('asset', function ($assetQuery) use ($branchId): void {
                $assetQuery->where('status', FixedAsset::STATUS_ACTIVE);
                if ($branchId) {
                    $assetQuery->where('branch_id', $branchId);
                }
            });

        return $query->get()->map(function (FixedAssetDepreciationSchedule $schedule) {
            $asset = $schedule->asset;
            if (! $asset instanceof FixedAsset) {
                return null;
            }
            if (FixedAssetDepreciationEntry::query()
                ->where('idempotency_key', $asset->id.':'.$schedule->period)
                ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
                ->exists()) {
                return null;
            }

            return [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'schedule_id' => $schedule->id,
                'amount' => AccountingMoney::toFloat($schedule->amount),
                'depreciation_expense_account_id' => $asset->depreciation_expense_account_id,
                'accumulated_depreciation_account_id' => $asset->accumulated_depreciation_account_id,
            ];
        })->filter()->values();
    }

    private function existingPostedRun(string $period, ?int $branchId): ?FixedAssetDepreciationRun
    {
        return FixedAssetDepreciationRun::query()
            ->where('idempotency_key', $this->runKey($period, $branchId))
            ->where('status', FixedAssetDepreciationRun::STATUS_POSTED)
            ->first();
    }

    private function runKey(string $period, ?int $branchId): string
    {
        return $period.':'.($branchId ?: 'all');
    }

    private function assertPeriod(string $period): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw ValidationException::withMessages(['period' => ['صيغة الفترة يجب أن تكون YYYY-MM.']]);
        }
    }

    private function lifeInMonths(FixedAsset $asset): int
    {
        $life = (int) $asset->useful_life;
        if (($asset->useful_life_unit ?? 'months') === 'years') {
            return $life * 12;
        }

        return $life;
    }

    private function refreshAssetStatus(FixedAsset $asset): void
    {
        $pending = FixedAssetDepreciationSchedule::query()
            ->where('fixed_asset_id', $asset->id)
            ->where('status', FixedAssetDepreciationSchedule::STATUS_PENDING)
            ->exists();
        if (! $pending && $asset->status === FixedAsset::STATUS_ACTIVE) {
            $asset->forceFill(['status' => FixedAsset::STATUS_FULLY_DEPRECIATED])->save();
        }
    }
}
