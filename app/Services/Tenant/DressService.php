<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Dress;
use App\Models\Tenant\DressCategory;
use App\Models\Tenant\DressImage;
use App\Models\Tenant\InventoryMovement;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DressService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly DressImageStorageService $dressImageStorageService,
    ) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Dress::query()
            ->with(['category', 'subcategory', 'branch', 'images'])
            ->latest('id');

        $searchTerm = trim((string) ($filters['search'] ?? ''));
        if ($searchTerm !== '') {
            $wildcard = '%'.mb_strtolower($searchTerm).'%';
            $query->where(function (Builder $builder) use ($wildcard): void {
                $builder->whereRaw('LOWER(code) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(color) LIKE ?', [$wildcard])
                    ->orWhereRaw('LOWER(size) LIKE ?', [$wildcard])
                    ->orWhereHas('category', fn (Builder $q) => $q->whereRaw('LOWER(name) LIKE ?', [$wildcard]))
                    ->orWhereHas('subcategory', fn (Builder $q) => $q->whereRaw('LOWER(name) LIKE ?', [$wildcard]));
            });
        }

        $this->applyExactFilter($query, 'dress_category_id', $filters['dress_category_id'] ?? null);
        $this->applyExactFilter($query, 'dress_subcategory_id', $filters['dress_subcategory_id'] ?? null);
        $this->applyExactFilter($query, 'id', $filters['id'] ?? null);
        $this->applyExactFilter($query, 'branch_id', $filters['branch_id'] ?? null);
        app(\App\Support\Tenant\AuthorizedBranchAccess::class)->constrain($query);
        $this->applyExactFilter($query, 'entity_type', $filters['entity_type'] ?? null);
        $this->applyExactFilter($query, 'entity_id', $filters['entity_id'] ?? null);
        $this->applyExactFilter($query, 'status', $filters['status'] ?? null);
        $this->applyExactFilter($query, 'code', $filters['code'] ?? null);
        $this->applyExactFilter($query, 'name', $filters['name'] ?? null);
        $this->applyExactFilter($query, 'color', $filters['color'] ?? null);
        $this->applyExactFilter($query, 'size', $filters['size'] ?? null);
        $this->applyExactFilter($query, 'delivery_date', $filters['delivery_date'] ?? null);
        $this->applyExactFilter($query, 'days_of_rent', $filters['days_of_rent'] ?? null);

        if (isset($filters['category_id'])) {
            $this->applyExactFilter($query, 'dress_category_id', $filters['category_id']);
        }

        if (isset($filters['subcat_id'])) {
            $this->applyExactFilter($query, 'dress_subcategory_id', $filters['subcat_id']);
        }

        $createdFrom = trim((string) ($filters['created_from'] ?? ''));
        if ($createdFrom !== '') {
            $query->whereDate('created_at', '>=', $createdFrom);
        }

        $createdTo = trim((string) ($filters['created_to'] ?? ''));
        if ($createdTo !== '') {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        $occasionDatetime = trim((string) ($filters['occasion_datetime'] ?? ''));
        if ($occasionDatetime !== '') {
            $query->whereDate('occasion_datetime', '=', $occasionDatetime);
        }

        $visitDatetime = trim((string) ($filters['visit_datetime'] ?? ''));
        if ($visitDatetime !== '') {
            $query->whereDate('visit_datetime', '=', $visitDatetime);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data, ?int $actorId = null, ?UploadedFile $image = null): Dress
    {
        $data = $this->prepareDressInput($data);

        if ($image === null) {
            throw ValidationException::withMessages([
                'image' => ['صورة المنتج مطلوبة.'],
            ]);
        }

        /** @var Dress $dress */
        $dress = DB::connection('tenant')->transaction(function () use ($data, $actorId, $image): Dress {
            $dress = Dress::query()->create($data);

            $this->attachPrimaryImage($dress, $image);

            $this->inventoryService->recordMovement(
                dress: $dress,
                type: InventoryMovement::TYPE_CREATED,
                reason: 'Dress created',
                notes: $dress->notes,
                createdBy: $actorId,
            );

            return $dress;
        });

        return $this->syncDisplayName($dress);
    }

    public function findOrFail(int $dressId): Dress
    {
        return Dress::query()->with(['category', 'subcategory', 'branch', 'images'])->findOrFail($dressId);
    }

    public function update(Dress $dress, array $data, ?int $actorId = null, ?UploadedFile $image = null): Dress
    {
        $originalStatus = (string) $dress->status;
        $newStatus = (string) ($data['status'] ?? $originalStatus);

        /** @var Dress $updatedDress */
        $updatedDress = DB::connection('tenant')->transaction(function () use ($dress, $data, $actorId, $originalStatus, $newStatus, $image): Dress {
            $dress->fill($this->prepareDressInput($data));
            $dress->save();

            if ($image !== null) {
                $this->replacePrimaryImage($dress, $image);
            }

            if ($newStatus !== $originalStatus) {
                $this->inventoryService->recordMovement(
                    dress: $dress,
                    type: InventoryMovement::TYPE_STATUS_CHANGED,
                    reason: sprintf('Status changed from %s to %s', $originalStatus, $newStatus),
                    notes: $dress->notes,
                    createdBy: $actorId,
                );
            }

            return $dress;
        });

        return $this->syncDisplayName($updatedDress);
    }

    private function attachPrimaryImage(Dress $dress, UploadedFile $image): void
    {
        $path = $this->dressImageStorageService->store($image);

        DressImage::query()->create([
            'dress_id' => $dress->id,
            'path' => $path,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    private function replacePrimaryImage(Dress $dress, UploadedFile $image): void
    {
        $path = $this->dressImageStorageService->store($image);

        $primary = $dress->images()->where('is_primary', true)->first()
            ?? $dress->images()->orderBy('sort_order')->first();

        if ($primary !== null) {
            $primary->path = $path;
            $primary->is_primary = true;
            $primary->save();

            return;
        }

        DressImage::query()->create([
            'dress_id' => $dress->id,
            'path' => $path,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    public function delete(Dress $dress): void
    {
        $dress->delete();
    }

    public function transferToBranch(Dress $dress, int $toBranchId, ?string $notes = null, ?int $actorId = null): Dress
    {
        $this->assertTransferAllowed($dress, $toBranchId);

        $fromBranchId = $dress->branch_id;

        /** @var Dress $updated */
        $updated = DB::connection('tenant')->transaction(function () use ($dress, $toBranchId, $fromBranchId, $notes, $actorId): Dress {
            $dress->branch_id = $toBranchId;
            $dress->save();

            $this->inventoryService->recordMovement(
                $dress,
                InventoryMovement::TYPE_BRANCH_TRANSFER,
                1,
                'Branch transfer',
                null,
                null,
                $notes,
                $actorId,
                $fromBranchId,
                $toBranchId,
            );

            return $dress;
        });

        return $this->syncDisplayName($updated->load(['category', 'subcategory', 'branch', 'images']));
    }

    /**
     * Transfer is blocked for sold/rented dresses and for dresses with an active rental order.
     */
    public function assertTransferAllowed(Dress $dress, int $toBranchId): void
    {
        if (! Branch::query()->whereKey($toBranchId)->exists()) {
            throw ValidationException::withMessages([
                'to_branch_id' => ['الفرع المستهدف غير موجود.'],
            ]);
        }

        if ((int) $dress->branch_id === $toBranchId) {
            throw ValidationException::withMessages([
                'to_branch_id' => ['يجب اختيار فرع مختلف عن الفرع الحالي.'],
            ]);
        }

        $status = (string) $dress->status;

        if ($status === Dress::STATUS_SOLD) {
            throw ValidationException::withMessages([
                'dress' => ['لا يمكن نقل منتج مباع.'],
            ]);
        }

        if ($status === Dress::STATUS_RENTED) {
            throw ValidationException::withMessages([
                'dress' => ['لا يمكن نقل منتج مؤجر حاليًا.'],
            ]);
        }

        if ($this->hasActiveRental($dress)) {
            throw ValidationException::withMessages([
                'dress' => ['لا يمكن نقل المنتج لوجود إيجار نشط عليه.'],
            ]);
        }
    }

    public function hasActiveRental(Dress $dress): bool
    {
        return Invoice::query()
            ->where('type', Invoice::TYPE_RENT)
            ->whereIn('status', [
                Invoice::STATUS_CONFIRMED,
                Invoice::STATUS_PARTIALLY_PAID,
                Invoice::STATUS_PAID,
                Invoice::STATUS_DELIVERED,
            ])
            ->whereHas('items', function (Builder $builder) use ($dress): void {
                $builder->where('dress_id', $dress->id);
            })
            ->exists();
    }

    public function orderHistory(Dress $dress, int $perPage = 15): LengthAwarePaginator
    {
        return InvoiceItem::query()
            ->where('dress_id', $dress->id)
            ->with('invoice')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{ranges:list<array{invoice_id:int,invoice_number:?string,start_date:?string,end_date:?string}>,days:list<string>}
     */
    public function unavailableDays(Dress $dress): array
    {
        $invoices = Invoice::query()
            ->where('type', Invoice::TYPE_RENT)
            ->whereIn('status', [
                Invoice::STATUS_CONFIRMED,
                Invoice::STATUS_PARTIALLY_PAID,
                Invoice::STATUS_PAID,
                Invoice::STATUS_DELIVERED,
                Invoice::STATUS_RETURNED,
            ])
            ->whereHas('items', function (Builder $builder) use ($dress): void {
                $builder->where('dress_id', $dress->id);
            })
            ->orderBy('rent_start_date')
            ->get(['id', 'invoice_number', 'rent_start_date', 'rent_end_date']);

        $ranges = [];
        $days = [];

        foreach ($invoices as $invoice) {
            $ranges[] = [
                'invoice_id' => (int) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'start_date' => $invoice->rent_start_date?->toDateString(),
                'end_date' => $invoice->rent_end_date?->toDateString(),
            ];

            if ($invoice->rent_start_date === null || $invoice->rent_end_date === null) {
                continue;
            }

            $cursor = $invoice->rent_start_date->copy();
            $endDate = $invoice->rent_end_date->copy();
            while ($cursor->lte($endDate)) {
                $days[] = $cursor->toDateString();
                $cursor->addDay();
            }
        }

        return [
            'ranges' => $ranges,
            'days' => array_values(array_unique($days)),
        ];
    }

    public function availableForDate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $startDate = (string) ($filters['start_date'] ?? $filters['date'] ?? '');
        $endDate = (string) ($filters['end_date'] ?? $filters['date'] ?? '');

        $query = Dress::query()
            ->with(['category', 'subcategory', 'branch', 'images'])
            ->latest('id');

        $this->applyExactFilter($query, 'branch_id', $filters['branch_id'] ?? null);
        if (isset($filters['category_id'])) {
            $this->applyExactFilter($query, 'dress_category_id', $filters['category_id']);
        }
        if (isset($filters['subcat_id'])) {
            $this->applyExactFilter($query, 'dress_subcategory_id', $filters['subcat_id']);
        }
        if (isset($filters['status'])) {
            $this->applyExactFilter($query, 'status', $filters['status']);
        } else {
            $query->where('status', Dress::STATUS_AVAILABLE);
        }

        if ($startDate !== '' && $endDate !== '') {
            $blockedDressIds = InvoiceItem::query()
                ->whereNotNull('dress_id')
                ->whereHas('invoice', function (Builder $builder) use ($startDate, $endDate): void {
                    $builder->where('type', Invoice::TYPE_RENT)
                        ->whereIn('status', [
                            Invoice::STATUS_CONFIRMED,
                            Invoice::STATUS_PARTIALLY_PAID,
                            Invoice::STATUS_PAID,
                            Invoice::STATUS_DELIVERED,
                        ])
                        ->whereDate('rent_start_date', '<=', $endDate)
                        ->whereDate('rent_end_date', '>=', $startDate);
                })
                ->pluck('dress_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($blockedDressIds !== []) {
                $query->whereNotIn('id', $blockedDressIds);
            }
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return list<array<int|string,mixed>>
     */
    public function exportRows(array $filters): array
    {
        $rows = $this->paginate($filters, 1000)->items();

        return array_map(static function (Dress $dress): array {
            return [
                $dress->id,
                $dress->code,
                $dress->name,
                $dress->status,
                $dress->branch_id,
                $dress->dress_category_id,
                $dress->dress_subcategory_id,
                $dress->entity_type,
                $dress->entity_id,
                $dress->rental_price,
                $dress->sale_price,
                $dress->created_at?->toDateTimeString(),
            ];
        }, $rows);
    }

    private function applyExactFilter(Builder $query, string $column, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return;
        }

        $query->where($column, $normalized);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareDressInput(array $data): array
    {
        $allowed = [
            'code',
            'dress_category_id',
            'dress_subcategory_id',
            'branch_id',
            'description',
            'status',
            'size',
            'color',
            'breast_size',
            'waist_size',
            'sleeve_size',
            'measurements',
            'purchase_price',
            'rental_price',
            'sale_price',
            'notes',
        ];

        $filtered = array_intersect_key($data, array_flip($allowed));
        $filtered['name'] = $this->buildDisplayName(array_merge($data, $filtered));

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildDisplayName(array $data): string
    {
        $code = trim((string) ($data['code'] ?? ''));
        $category = isset($data['dress_category_id'])
            ? DressCategory::query()->find((int) $data['dress_category_id'])
            : null;
        $subcategory = isset($data['dress_subcategory_id'])
            ? DressCategory::query()->find((int) $data['dress_subcategory_id'])
            : null;

        $parts = array_values(array_filter([
            $code,
            $category?->name,
            $subcategory?->name,
        ], fn (?string $value): bool => is_string($value) && trim($value) !== ''));

        return implode('-', $parts);
    }

    private function syncDisplayName(Dress $dress): Dress
    {
        $dress->load(['category', 'subcategory', 'branch', 'images']);
        $dress->name = $dress->displayName();
        $dress->save();

        return $dress->refresh()->load(['category', 'subcategory', 'branch', 'images']);
    }
}
