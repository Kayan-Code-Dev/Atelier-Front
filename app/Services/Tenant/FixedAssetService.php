<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingMoney;
use App\Models\Tenant\Account;
use App\Models\Tenant\FixedAsset;
use App\Models\Tenant\FixedAssetCategory;
use App\Models\Tenant\FixedAssetDepreciationEntry;
use App\Models\Tenant\FixedAssetDepreciationSchedule;
use App\Models\Tenant\FixedAssetDisposal;
use App\Models\Tenant\FixedAssetTransaction;
use App\Models\Tenant\FixedAssetTransfer;
use App\Models\Tenant\JournalEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FixedAssetService
{
    public function __construct(
        private readonly JournalEntryService $journals,
        private readonly AccountingAuditService $audit,
        private readonly DepreciationService $depreciation,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = FixedAsset::query()->with(['category:id,name,code', 'branch:id,name']);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        return FixedAssetCategory::query()
            ->with(['assetAccount:id,code,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (FixedAssetCategory $category) => $this->presentCategory($category))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveCategory(array $data, ?int $id = null): FixedAssetCategory
    {
        $payload = [
            'name' => trim((string) $data['name']),
            'code' => strtoupper(trim((string) $data['code'])),
            'description' => $data['description'] ?? null,
            'asset_account_id' => $this->requirePostingAccount((int) $data['asset_account_id'])->id,
            'accumulated_depreciation_account_id' => $this->requirePostingAccount((int) $data['accumulated_depreciation_account_id'])->id,
            'depreciation_expense_account_id' => $this->requirePostingAccount((int) $data['depreciation_expense_account_id'])->id,
            'disposal_gain_loss_account_id' => $this->requirePostingAccount((int) $data['disposal_gain_loss_account_id'])->id,
            'active' => array_key_exists('active', $data) ? (bool) $data['active'] : true,
        ];

        $category = $id
            ? tap(FixedAssetCategory::query()->findOrFail($id))->update($payload)
            : FixedAssetCategory::query()->create($payload);

        return $category->fresh() ?? $category;
    }

    public function findOrFail(int $id): FixedAsset
    {
        return FixedAsset::query()
            ->with([
                'category',
                'branch:id,name',
                'purchaseJournal',
                'schedules',
                'transfers.fromBranch:id,name',
                'transfers.toBranch:id,name',
                'disposals.journalEntry',
                'transactions.journalEntry',
            ])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId): FixedAsset
    {
        $this->assertFinancials($data);
        $category = FixedAssetCategory::query()->findOrFail((int) $data['category_id']);
        if (! $category->active) {
            throw ValidationException::withMessages(['category_id' => ['التصنيف غير نشط.']]);
        }

        return DB::connection('tenant')->transaction(function () use ($data, $category, $actorId): FixedAsset {
            $asset = FixedAsset::query()->create([
                'branch_id' => $data['branch_id'] ?? null,
                'category_id' => $category->id,
                'asset_code' => $this->nextCode($data['asset_code'] ?? null),
                'name' => trim((string) $data['name']),
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'placed_in_service_date' => $data['placed_in_service_date'] ?? $data['purchase_date'],
                'acquisition_cost' => AccountingMoney::of($data['acquisition_cost']),
                'salvage_value' => AccountingMoney::of($data['salvage_value'] ?? 0),
                'useful_life' => (int) $data['useful_life'],
                'useful_life_unit' => $data['useful_life_unit'] ?? 'months',
                'depreciation_method' => FixedAsset::METHOD_STRAIGHT_LINE,
                'acquisition_method' => $data['acquisition_method'] ?? FixedAsset::ACQUIRE_CASH,
                'status' => FixedAsset::STATUS_DRAFT,
                'asset_account_id' => (int) ($data['asset_account_id'] ?? $category->asset_account_id),
                'accumulated_depreciation_account_id' => (int) ($data['accumulated_depreciation_account_id'] ?? $category->accumulated_depreciation_account_id),
                'depreciation_expense_account_id' => (int) ($data['depreciation_expense_account_id'] ?? $category->depreciation_expense_account_id),
                'disposal_gain_loss_account_id' => (int) ($data['disposal_gain_loss_account_id'] ?? $category->disposal_gain_loss_account_id),
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'attachments' => $data['attachments'] ?? null,
            ]);

            $this->depreciation->rebuildSchedule($asset);
            $this->audit->record($actorId, 'created', 'fixed_asset', $asset->id, [
                'asset_code' => $asset->asset_code,
            ]);

            if (($data['post_purchase'] ?? true) !== false) {
                $asset = $this->postPurchase($asset, $actorId);
            }

            return $this->findOrFail($asset->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FixedAsset $asset, array $data, ?int $actorId): FixedAsset
    {
        if ($asset->isClosed()) {
            throw ValidationException::withMessages(['status' => ['لا يمكن تعديل أصل تم التصرف فيه أو إخراجه من الخدمة.']]);
        }
        if ($asset->purchase_journal_entry_id && $asset->status !== FixedAsset::STATUS_DRAFT) {
            $allowed = ['name', 'description', 'location', 'attachments'];
            $data = array_intersect_key($data, array_flip($allowed));
        } else {
            $this->assertFinancials(array_merge($asset->toArray(), $data));
        }

        $asset->fill($data);
        $asset->updated_by = $actorId;
        $asset->save();
        if ($asset->status === FixedAsset::STATUS_DRAFT) {
            $this->depreciation->rebuildSchedule($asset->fresh() ?? $asset);
        }
        $this->audit->record($actorId, 'updated', 'fixed_asset', $asset->id);

        return $this->findOrFail($asset->id);
    }

    public function postPurchase(FixedAsset $asset, ?int $actorId): FixedAsset
    {
        if ($asset->isClosed()) {
            throw ValidationException::withMessages(['status' => ['لا يمكن شراء أصل خارج الخدمة.']]);
        }
        if ($asset->purchase_journal_entry_id) {
            return $asset;
        }

        $creditAccountId = $asset->acquisition_method === FixedAsset::ACQUIRE_PAYABLE
            ? (int) ($asset->payment_account_id ?: $this->accountIdByCode('2000'))
            : (int) ($asset->payment_account_id ?: $this->accountIdByCode('1000'));

        $this->requirePostingAccount((int) $asset->asset_account_id);
        $this->requirePostingAccount($creditAccountId);

        $cost = AccountingMoney::of($asset->acquisition_cost);
        $journal = $this->journals->createFromSource([
            'entry_date' => $asset->purchase_date?->toDateString() ?? now()->toDateString(),
            'source_type' => JournalEntry::SOURCE_FIXED_ASSET,
            'source_id' => $asset->id,
            'reference_number' => $asset->asset_code,
            'description' => 'شراء أصل ثابت: '.$asset->name,
            'branch_id' => $asset->branch_id,
        ], [
            ['account_id' => (int) $asset->asset_account_id, 'debit' => $cost, 'credit' => 0, 'description' => $asset->name],
            ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $cost, 'description' => $asset->name],
        ], $actorId);

        $this->assertBalancedJournal($journal);

        $asset->forceFill([
            'purchase_journal_entry_id' => $journal->id,
            'status' => FixedAsset::STATUS_ACTIVE,
            'updated_by' => $actorId,
        ])->save();

        $this->recordTransaction($asset, 'purchase', $asset->purchase_date?->toDateString(), $cost, $journal->id, $actorId, [
            'acquisition_method' => $asset->acquisition_method,
        ]);
        $this->audit->record($actorId, 'posted', 'fixed_asset', $asset->id, [
            'journal_entry_id' => $journal->id,
        ]);

        return $asset->fresh() ?? $asset;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function transfer(FixedAsset $asset, array $data, ?int $actorId): FixedAsset
    {
        if ($asset->isClosed()) {
            throw ValidationException::withMessages(['status' => ['لا يمكن نقل أصل خارج الخدمة.']]);
        }

        $toBranchId = isset($data['to_branch_id']) ? (int) $data['to_branch_id'] : null;
        $toLocation = $data['to_location'] ?? $asset->location;
        if ((int) $asset->branch_id === (int) $toBranchId && (string) $asset->location === (string) $toLocation) {
            throw ValidationException::withMessages(['to_branch_id' => ['لم يتغير فرع أو موقع الأصل.']]);
        }

        return DB::connection('tenant')->transaction(function () use ($asset, $data, $toBranchId, $toLocation, $actorId): FixedAsset {
            $transfer = FixedAssetTransfer::query()->create([
                'fixed_asset_id' => $asset->id,
                'transferred_at' => $data['transferred_at'] ?? now()->toDateString(),
                'from_branch_id' => $asset->branch_id,
                'to_branch_id' => $toBranchId,
                'from_location' => $asset->location,
                'to_location' => $toLocation,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
            ]);

            $asset->forceFill([
                'branch_id' => $toBranchId,
                'location' => $toLocation,
                'updated_by' => $actorId,
            ])->save();

            $this->recordTransaction($asset, 'transfer', $transfer->transferred_at?->toDateString(), '0', null, $actorId, [
                'transfer_id' => $transfer->id,
                'from_branch_id' => $transfer->from_branch_id,
                'to_branch_id' => $transfer->to_branch_id,
            ]);
            $this->audit->record($actorId, 'transferred', 'fixed_asset', $asset->id, [
                'transfer_id' => $transfer->id,
            ]);

            return $this->findOrFail($asset->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function previewDisposal(FixedAsset $asset, array $data = []): array
    {
        $cost = AccountingMoney::of($asset->acquisition_cost);
        $accum = $this->accumulatedDepreciation($asset);
        $book = AccountingMoney::sub($cost, $accum);
        $proceeds = AccountingMoney::of($data['proceeds'] ?? 0);
        $gainLoss = AccountingMoney::sub($proceeds, $book);

        return [
            'acquisition_cost' => AccountingMoney::toFloat($cost),
            'accumulated_depreciation' => AccountingMoney::toFloat($accum),
            'net_book_value' => AccountingMoney::toFloat($book),
            'proceeds' => AccountingMoney::toFloat($proceeds),
            'gain_loss' => AccountingMoney::toFloat($gainLoss),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function dispose(FixedAsset $asset, array $data, ?int $actorId): FixedAsset
    {
        if ($asset->isClosed()) {
            throw ValidationException::withMessages(['status' => ['تم التصرف في هذا الأصل مسبقاً.']]);
        }
        if ($asset->status === FixedAsset::STATUS_DRAFT) {
            throw ValidationException::withMessages(['status' => ['لا يمكن التصرف في أصل غير مرحّل.']]);
        }

        $type = (string) ($data['type'] ?? FixedAssetDisposal::TYPE_SALE);
        if (! in_array($type, [FixedAssetDisposal::TYPE_SALE, FixedAssetDisposal::TYPE_RETIREMENT, FixedAssetDisposal::TYPE_LOSS, FixedAssetDisposal::TYPE_DAMAGE], true)) {
            throw ValidationException::withMessages(['type' => ['نوع التصرف غير صالح.']]);
        }

        $preview = $this->previewDisposal($asset, $data);
        $cost = AccountingMoney::of($preview['acquisition_cost']);
        $accum = AccountingMoney::of($preview['accumulated_depreciation']);
        $proceeds = AccountingMoney::of($preview['proceeds']);
        $gainLoss = AccountingMoney::of($preview['gain_loss']);
        $date = $data['disposed_at'] ?? now()->toDateString();

        return DB::connection('tenant')->transaction(function () use ($asset, $type, $preview, $cost, $accum, $proceeds, $gainLoss, $date, $data, $actorId): FixedAsset {
            $disposal = FixedAssetDisposal::query()->create([
                'fixed_asset_id' => $asset->id,
                'type' => $type,
                'disposed_at' => $date,
                'acquisition_cost' => $cost,
                'accumulated_depreciation' => $accum,
                'net_book_value' => AccountingMoney::of($preview['net_book_value']),
                'proceeds' => $proceeds,
                'gain_loss' => $gainLoss,
                'proceeds_account_id' => $data['proceeds_account_id'] ?? $asset->payment_account_id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
            ]);

            $lines = [];
            if (AccountingMoney::isPositive($accum)) {
                $lines[] = [
                    'account_id' => (int) $asset->accumulated_depreciation_account_id,
                    'debit' => $accum,
                    'credit' => 0,
                    'description' => 'إقفال مجمع الإهلاك',
                ];
            }
            if (AccountingMoney::isPositive($proceeds)) {
                $lines[] = [
                    'account_id' => (int) ($disposal->proceeds_account_id ?: $this->accountIdByCode('1000')),
                    'debit' => $proceeds,
                    'credit' => 0,
                    'description' => 'متحصلات التصرف',
                ];
            }
            if (AccountingMoney::cmp($gainLoss, '0') < 0) {
                $lines[] = [
                    'account_id' => (int) $asset->disposal_gain_loss_account_id,
                    'debit' => AccountingMoney::abs($gainLoss),
                    'credit' => 0,
                    'description' => 'خسارة التصرف',
                ];
            }
            $lines[] = [
                'account_id' => (int) $asset->asset_account_id,
                'debit' => 0,
                'credit' => $cost,
                'description' => 'إخراج الأصل',
            ];
            if (AccountingMoney::cmp($gainLoss, '0') > 0) {
                $lines[] = [
                    'account_id' => (int) $asset->disposal_gain_loss_account_id,
                    'debit' => 0,
                    'credit' => $gainLoss,
                    'description' => 'ربح التصرف',
                ];
            }

            $journal = $this->journals->createFromSource([
                'entry_date' => $date,
                'source_type' => JournalEntry::SOURCE_ASSET_DISPOSAL,
                'source_id' => $asset->id,
                'reference_number' => $asset->asset_code,
                'description' => 'تصرف في أصل ثابت: '.$asset->name,
                'branch_id' => $asset->branch_id,
            ], $lines, $actorId);
            $this->assertBalancedJournal($journal);

            $disposal->forceFill([
                'journal_entry_id' => $journal->id,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ])->save();

            $status = $type === FixedAssetDisposal::TYPE_SALE
                ? FixedAsset::STATUS_DISPOSED
                : FixedAsset::STATUS_RETIRED;
            $asset->forceFill([
                'status' => $status,
                'updated_by' => $actorId,
            ])->save();

            $this->recordTransaction($asset, 'disposal', $date, $proceeds, $journal->id, $actorId, [
                'disposal_id' => $disposal->id,
                'type' => $type,
                'gain_loss' => $preview['gain_loss'],
            ]);
            $this->audit->record($actorId, 'disposed', 'fixed_asset', $asset->id, [
                'journal_entry_id' => $journal->id,
                'type' => $type,
            ]);

            return $this->findOrFail($asset->id);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function present(FixedAsset $asset): array
    {
        $cost = AccountingMoney::of($asset->acquisition_cost);
        $accum = $this->accumulatedDepreciation($asset);
        $book = AccountingMoney::sub($cost, $accum);

        return [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'description' => $asset->description,
            'location' => $asset->location,
            'status' => $asset->status,
            'category' => $asset->category ? [
                'id' => $asset->category->id,
                'name' => $asset->category->name,
                'code' => $asset->category->code,
            ] : null,
            'branch_id' => $asset->branch_id,
            'branch_name' => $asset->branch?->name,
            'purchase_date' => $asset->purchase_date?->toDateString(),
            'placed_in_service_date' => $asset->placed_in_service_date?->toDateString(),
            'acquisition_cost' => AccountingMoney::toFloat($cost),
            'salvage_value' => AccountingMoney::toFloat($asset->salvage_value),
            'useful_life' => $asset->useful_life,
            'useful_life_unit' => $asset->useful_life_unit,
            'depreciation_method' => $asset->depreciation_method,
            'acquisition_method' => $asset->acquisition_method,
            'accumulated_depreciation' => AccountingMoney::toFloat($accum),
            'net_book_value' => AccountingMoney::toFloat($book),
            'asset_account_id' => $asset->asset_account_id,
            'accumulated_depreciation_account_id' => $asset->accumulated_depreciation_account_id,
            'depreciation_expense_account_id' => $asset->depreciation_expense_account_id,
            'disposal_gain_loss_account_id' => $asset->disposal_gain_loss_account_id,
            'payment_account_id' => $asset->payment_account_id,
            'purchase_journal_entry_id' => $asset->purchase_journal_entry_id,
            'purchase_journal' => $asset->purchaseJournal ? [
                'id' => $asset->purchaseJournal->id,
                'entry_number' => $asset->purchaseJournal->entry_number,
                'status' => $asset->purchaseJournal->status,
            ] : null,
            'attachments' => $asset->attachments ?? [],
            'created_by' => $asset->created_by,
            'updated_by' => $asset->updated_by,
            'created_at' => $asset->created_at?->toIso8601String(),
            'updated_at' => $asset->updated_at?->toIso8601String(),
        ];
    }

    public function accumulatedDepreciation(FixedAsset $asset): string
    {
        $posted = FixedAssetDepreciationEntry::query()
            ->where('fixed_asset_id', $asset->id)
            ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
            ->sum('amount');

        return AccountingMoney::of($posted);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertFinancials(array $data): void
    {
        $cost = AccountingMoney::of($data['acquisition_cost'] ?? 0);
        $salvage = AccountingMoney::of($data['salvage_value'] ?? 0);
        if (AccountingMoney::cmp($cost, '0') < 0) {
            throw ValidationException::withMessages(['acquisition_cost' => ['تكلفة الاقتناء لا يمكن أن تكون سالبة.']]);
        }
        if (AccountingMoney::cmp($salvage, '0') < 0) {
            throw ValidationException::withMessages(['salvage_value' => ['القيمة المتبقية لا يمكن أن تكون سالبة.']]);
        }
        if (AccountingMoney::cmp($salvage, $cost) > 0) {
            throw ValidationException::withMessages(['salvage_value' => ['القيمة المتبقية لا يجوز أن تتجاوز تكلفة الاقتناء.']]);
        }
        if ((int) ($data['useful_life'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['useful_life' => ['العمر الإنتاجي يجب أن يكون أكبر من صفر.']]);
        }
        $method = $data['depreciation_method'] ?? FixedAsset::METHOD_STRAIGHT_LINE;
        if ($method !== FixedAsset::METHOD_STRAIGHT_LINE) {
            throw ValidationException::withMessages(['depreciation_method' => ['Sprint 3 يدعم الإهلاك الخط المستقيم فقط.']]);
        }
    }

    private function nextCode(?string $requested): string
    {
        $code = strtoupper(trim((string) $requested));
        if ($code !== '') {
            if (FixedAsset::query()->where('asset_code', $code)->exists()) {
                throw ValidationException::withMessages(['asset_code' => ['كود الأصل مستخدم مسبقاً.']]);
            }

            return $code;
        }

        $year = now()->year;
        $count = FixedAsset::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('FA-%d-%04d', $year, $count);
    }

    private function requirePostingAccount(int $id): Account
    {
        $account = Account::query()->findOrFail($id);
        if (! $account->allowsPosting()) {
            throw ValidationException::withMessages(['account_id' => ['الحساب المحدد غير قابل للترحيل.']]);
        }

        return $account;
    }

    private function accountIdByCode(string $code): int
    {
        $account = Account::query()->where('code', $code)->first();
        if ($account === null) {
            throw ValidationException::withMessages(['account' => ["الحساب {$code} غير موجود في دليل الحسابات."]]);
        }

        return (int) $account->id;
    }

    private function assertBalancedJournal(JournalEntry $journal): void
    {
        if (! $journal->is_balanced || AccountingMoney::cmp($journal->total_debit, $journal->total_credit) !== 0) {
            throw ValidationException::withMessages(['journal' => ['القيد المحاسبي غير متوازن.']]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordTransaction(
        FixedAsset $asset,
        string $type,
        ?string $date,
        mixed $amount,
        ?int $journalId,
        ?int $actorId,
        array $payload = []
    ): void {
        FixedAssetTransaction::query()->create([
            'fixed_asset_id' => $asset->id,
            'type' => $type,
            'occurred_at' => $date ?? now()->toDateString(),
            'amount' => AccountingMoney::of($amount),
            'journal_entry_id' => $journalId,
            'payload' => $payload,
            'created_by' => $actorId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentCategory(FixedAssetCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'code' => $category->code,
            'description' => $category->description,
            'asset_account_id' => $category->asset_account_id,
            'accumulated_depreciation_account_id' => $category->accumulated_depreciation_account_id,
            'depreciation_expense_account_id' => $category->depreciation_expense_account_id,
            'disposal_gain_loss_account_id' => $category->disposal_gain_loss_account_id,
            'active' => (bool) $category->active,
        ];
    }
}
