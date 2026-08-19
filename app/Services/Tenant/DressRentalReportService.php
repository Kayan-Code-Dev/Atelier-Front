<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Dress;
use App\Models\Tenant\InventoryMovement;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\RentalReturnSettlement;
use App\Models\Tenant\SecurityDepositTransaction;
use App\Models\Tenant\User;
use App\Support\Tenant\RentalOrderPresenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read-only dress rental performance & journey report.
 *
 * Payment allocation (reporting-only, does not mutate journals/payments):
 * share = dress_line.total / sum(invoice_items.total)
 * amounts attributed to the dress = round(invoice_or_settlement_amount * share, 2)
 *
 * Security deposits are liabilities and never counted as rental revenue.
 * Cancelled invoices contribute 0 to valid KPI totals.
 */
class DressRentalReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(Dress $dress, array $filters, ?User $actor = null): array
    {
        $dress->loadMissing(['category', 'subcategory', 'branch', 'images']);

        $dateFrom = $this->resolveDateFrom($dress, $filters['date_from'] ?? null);
        $dateTo = $this->resolveDateTo($filters['date_to'] ?? null);

        $rows = $this->buildRentalRows($dress, $dateFrom, $dateTo, $filters);
        $validRows = $rows->filter(fn (array $row): bool => ! $row['is_cancelled'])->values();
        $cancelledRows = $rows->filter(fn (array $row): bool => $row['is_cancelled'])->values();

        $canViewCustomers = $actor === null || $this->userHasPermission($actor, 'customers.view');

        $paginated = $this->paginateRows(
            $this->sortRows($rows, (string) ($filters['sort'] ?? 'rent_start_date'), (string) ($filters['direction'] ?? 'desc')),
            (int) ($filters['page'] ?? 1),
            (int) ($filters['per_page'] ?? 15),
            $canViewCustomers,
        );

        return [
            'dress' => $this->dressPayload($dress),
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'status' => $filters['status'] ?? 'all',
                'branch_id' => $filters['branch_id'] ?? null,
                'customer_id' => $filters['customer_id'] ?? null,
                'search' => $filters['search'] ?? null,
            ],
            'allocation_method' => 'pro_rata_by_invoice_item_total_share',
            'summary' => $this->buildSummary($validRows, $cancelledRows),
            'chart' => $this->buildChart($validRows, $dateFrom, $dateTo),
            'rentals' => $paginated,
            'journey' => $this->buildJourney($dress, $rows, (string) ($filters['journey_order'] ?? 'desc'), $canViewCustomers),
            'transfers' => $this->buildTransfers($dress),
            'customer_insights' => $this->buildCustomerInsights($validRows, $canViewCustomers),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dressPayload(Dress $dress): array
    {
        $createdAt = $dress->created_at ? Carbon::parse($dress->created_at)->startOfDay() : Carbon::today();
        $image = $dress->images->firstWhere('is_primary', true) ?? $dress->images->first();
        $imageUrl = $image !== null
            ? app(DressImageStorageService::class)->url($image->path)
            : null;

        return [
            'id' => (int) $dress->id,
            'name' => $dress->name,
            'code' => $dress->code,
            'display_name' => $dress->displayName(),
            'image' => $imageUrl,
            'category' => $dress->category ? [
                'id' => (int) $dress->category->id,
                'name' => $dress->category->name,
            ] : null,
            'subcategory' => $dress->subcategory ? [
                'id' => (int) $dress->subcategory->id,
                'name' => $dress->subcategory->name,
            ] : null,
            'current_branch' => $dress->branch ? [
                'id' => (int) $dress->branch->id,
                'name' => $dress->branch->name,
            ] : null,
            'current_status' => $dress->status,
            'availability_status' => $dress->status,
            'created_at' => $dress->created_at?->toISOString(),
            'days_in_business' => max(0, (int) $createdAt->diffInDays(Carbon::today())),
            'purchase_price' => $dress->purchase_price !== null ? $this->money((float) $dress->purchase_price) : null,
            'rental_price' => $dress->rental_price !== null ? $this->money((float) $dress->rental_price) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRentalRows(Dress $dress, Carbon $dateFrom, Carbon $dateTo, array $filters): Collection
    {
        $items = InvoiceItem::query()
            ->where('dress_id', $dress->id)
            ->whereHas('invoice', function (Builder $query) use ($dateFrom, $dateTo): void {
                $query->where('type', Invoice::TYPE_RENT)
                    ->where(function (Builder $dateQuery) use ($dateFrom, $dateTo): void {
                        $from = $dateFrom->toDateString();
                        $to = $dateTo->toDateString();
                        $fromDateTime = $dateFrom->copy()->startOfDay();
                        $toDateTime = $dateTo->copy()->endOfDay();

                        // Include rentals that start in-range, overlap the selected period,
                        // or were booked in-range for an upcoming start (after date_to).
                        $dateQuery
                            ->whereBetween('rent_start_date', [$from, $to])
                            ->orWhere(function (Builder $overlap) use ($from, $to): void {
                                $overlap->whereNotNull('rent_start_date')
                                    ->where('rent_start_date', '<=', $to)
                                    ->where(function (Builder $endBound) use ($from): void {
                                        $endBound->whereNull('rent_end_date')
                                            ->orWhere('rent_end_date', '>=', $from);
                                    });
                            })
                            ->orWhere(function (Builder $upcomingBooked) use ($fromDateTime, $toDateTime, $to): void {
                                $upcomingBooked->whereNotNull('rent_start_date')
                                    ->where('rent_start_date', '>', $to)
                                    ->whereBetween('created_at', [$fromDateTime, $toDateTime]);
                            })
                            ->orWhere(function (Builder $fallback) use ($fromDateTime, $toDateTime): void {
                                $fallback->whereNull('rent_start_date')
                                    ->whereBetween('created_at', [$fromDateTime, $toDateTime]);
                            });
                    });
            })
            ->with([
                'invoice.customer',
                'invoice.branch',
                'invoice.createdBy',
                'invoice.payments',
                'invoice.securityDepositTransactions',
                'invoice.items',
            ])
            ->get();

        $invoiceIds = $items->pluck('invoice_id')->unique()->filter()->values()->all();
        $settlements = RentalReturnSettlement::query()
            ->whereIn('invoice_id', $invoiceIds)
            ->get()
            ->keyBy('invoice_id');

        $statusFilter = (string) ($filters['status'] ?? 'all');
        $branchId = $filters['branch_id'] ?? null;
        $customerId = $filters['customer_id'] ?? null;
        $search = $filters['search'] ?? null;

        return $items->map(function (InvoiceItem $item) use ($settlements): array {
            /** @var Invoice $invoice */
            $invoice = $item->invoice;
            $settlement = $settlements->get($invoice->id);
            $share = $this->lineShare((float) $item->total, (float) $invoice->items->sum(fn ($line) => (float) $line->total));
            $isCancelled = $invoice->status === Invoice::STATUS_CANCELLED;
            $mappedStatus = RentalOrderPresenter::mapStatus($invoice);

            $baseRevenue = $isCancelled ? 0.0 : $this->allocate((float) $invoice->total, $share);
            $lateFee = $isCancelled ? 0.0 : $this->allocate((float) ($settlement?->late_fee ?? 0), $share);
            $damageFee = $isCancelled ? 0.0 : $this->allocate((float) ($settlement?->damage_fee ?? 0), $share);
            $cleaningFee = $isCancelled ? 0.0 : $this->allocate((float) ($settlement?->cleaning_fee ?? 0), $share);
            $otherFee = $isCancelled ? 0.0 : $this->allocate((float) ($settlement?->other_fee ?? 0), $share);
            $additionalFees = $this->money($lateFee + $damageFee + $cleaningFee + $otherFee);

            $collected = $isCancelled ? 0.0 : $this->allocate($this->invoiceCollectedAmount($invoice), $share);
            $depositsReceived = $isCancelled ? 0.0 : $this->allocate($this->depositsReceivedAmount($invoice, $settlement), $share);
            $depositsReturned = $isCancelled ? 0.0 : $this->allocate($this->depositsReturnedAmount($invoice, $settlement), $share);
            $lineDiscount = $isCancelled ? 0.0 : $this->allocate((float) ($invoice->discount ?? 0), $share);
            $amountDue = $this->money($baseRevenue + $additionalFees);
            $outstanding = $isCancelled ? 0.0 : $this->money(max(0, $amountDue - $collected));

            $rentalDays = $this->rentalDays($invoice);
            $isLate = false;
            if ($settlement !== null && ((int) $settlement->late_days > 0 || (float) $settlement->late_fee > 0)) {
                $isLate = true;
            } elseif ($invoice->return_date && $invoice->rent_end_date) {
                $isLate = Carbon::parse((string) $invoice->return_date)->gt(Carbon::parse((string) $invoice->rent_end_date));
            }

            $hasDamage = $settlement !== null && (
                (float) $settlement->damage_fee > 0
                || (string) $settlement->condition === 'damaged'
            );

            return [
                'invoice_item_id' => (int) $item->id,
                'invoice_id' => (int) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer_id ? (int) $invoice->customer_id : null,
                'customer_name' => $invoice->customer?->name,
                'customer_phone' => $invoice->customer?->phone,
                'branch_id' => $invoice->branch_id ? (int) $invoice->branch_id : null,
                'branch_name' => $invoice->branch?->name,
                'booking_date' => $invoice->created_at?->toDateString(),
                'rent_start_date' => $invoice->rent_start_date?->toDateString(),
                'rent_end_date' => $invoice->rent_end_date?->toDateString(),
                'delivery_date' => $invoice->delivery_date?->toDateString(),
                'return_date' => $invoice->return_date?->toDateString()
                    ?? ($settlement?->actual_return_date?->toDateString()),
                'rental_days' => $rentalDays,
                'invoice_status' => $invoice->status,
                'rental_status' => $mappedStatus,
                'return_status' => $invoice->status === Invoice::STATUS_RETURNED
                    ? 'returned'
                    : ($invoice->delivery_date ? 'awaiting_return' : 'not_delivered'),
                'payment_status' => RentalOrderPresenter::mapPaymentStatus($invoice),
                'base_rental_amount' => $this->moneyString($baseRevenue),
                'discount' => $this->moneyString($lineDiscount),
                'late_fee' => $this->moneyString($lateFee),
                'damage_fee' => $this->moneyString($damageFee),
                'cleaning_fee' => $this->moneyString($cleaningFee),
                'other_fee' => $this->moneyString($otherFee),
                'additional_fees' => $this->moneyString($additionalFees),
                'deposit' => $this->moneyString($this->allocate((float) ($invoice->security_deposit ?? 0), $share)),
                'deposits_received' => $this->moneyString($depositsReceived),
                'deposits_returned' => $this->moneyString($depositsReturned),
                'amount_due' => $this->moneyString($amountDue),
                'collected' => $this->moneyString($collected),
                'outstanding' => $this->moneyString($outstanding),
                'created_by' => $invoice->createdBy ? [
                    'id' => (int) $invoice->createdBy->id,
                    'name' => $invoice->createdBy->name,
                ] : null,
                'settlement_id' => $settlement?->id ? (int) $settlement->id : null,
                'is_cancelled' => $isCancelled,
                'is_late' => $isLate,
                'has_damage' => $hasDamage,
                'share' => round($share, 6),
                '_sort_created_at' => optional($invoice->created_at)->timestamp ?? 0,
                '_sort_rent_start' => $invoice->rent_start_date?->timestamp ?? optional($invoice->created_at)->timestamp ?? 0,
                '_sort_rent_end' => $invoice->rent_end_date?->timestamp ?? 0,
                '_sort_total' => $amountDue,
                '_base_revenue' => $baseRevenue,
                '_additional_fees' => $additionalFees,
                '_late_fee' => $lateFee,
                '_damage_fee' => $damageFee,
                '_cleaning_fee' => $cleaningFee,
                '_other_fee' => $otherFee,
                '_collected' => $collected,
                '_outstanding' => $outstanding,
                '_deposits_received' => $depositsReceived,
                '_deposits_returned' => $depositsReturned,
                '_rental_days' => $rentalDays,
            ];
        })->filter(function (array $row) use ($statusFilter, $branchId, $customerId, $search): bool {
            if ($statusFilter === 'valid' && $row['is_cancelled']) {
                return false;
            }
            if ($statusFilter !== 'all' && $statusFilter !== 'valid' && $row['rental_status'] !== $statusFilter) {
                return false;
            }
            if ($branchId !== null && (int) $row['branch_id'] !== (int) $branchId) {
                return false;
            }
            if ($customerId !== null && (int) $row['customer_id'] !== (int) $customerId) {
                return false;
            }
            if (is_string($search) && $search !== '') {
                $needle = mb_strtolower($search);
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $row['invoice_number'] ?? '',
                    $row['customer_name'] ?? '',
                    $row['customer_phone'] ?? '',
                ])));
                if (! str_contains($haystack, $needle)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $validRows
     * @param  Collection<int, array<string, mixed>>  $cancelledRows
     * @return array<string, mixed>
     */
    private function buildSummary(Collection $validRows, Collection $cancelledRows): array
    {
        $totalRentals = $validRows->count();
        $totalRentalDays = (int) $validRows->sum('_rental_days');
        $baseRevenue = $this->money((float) $validRows->sum('_base_revenue'));
        $lateFees = $this->money((float) $validRows->sum('_late_fee'));
        $damageFees = $this->money((float) $validRows->sum('_damage_fee'));
        $cleaningFees = $this->money((float) $validRows->sum('_cleaning_fee'));
        $otherFees = $this->money((float) $validRows->sum('_other_fee'));
        $additionalFees = $this->money($lateFees + $damageFees + $cleaningFees + $otherFees);
        $collected = $this->money((float) $validRows->sum('_collected'));
        $outstanding = $this->money((float) $validRows->sum('_outstanding'));
        $depositsReceived = $this->money((float) $validRows->sum('_deposits_received'));
        $depositsReturned = $this->money((float) $validRows->sum('_deposits_returned'));

        $lastRental = $validRows
            ->sortByDesc('_sort_rent_start')
            ->first();

        $uniqueCustomers = $validRows
            ->pluck('customer_id')
            ->filter()
            ->unique()
            ->count();

        return [
            'total_rentals' => $totalRentals,
            'completed_rentals' => $validRows->where('rental_status', 'returned')->count(),
            'active_and_upcoming_rentals' => $validRows->whereIn('rental_status', ['pending', 'active', 'overdue'])->count(),
            'cancelled_rentals' => $cancelledRows->count(),
            'total_rental_days' => $totalRentalDays,
            'average_rental_days' => $totalRentals > 0 ? round($totalRentalDays / $totalRentals, 2) : 0,
            'late_return_count' => $validRows->where('is_late', true)->count(),
            'damage_count' => $validRows->where('has_damage', true)->count(),
            'last_rental_at' => $lastRental['rent_start_date'] ?? $lastRental['booking_date'] ?? null,
            'base_rental_revenue' => $this->moneyString($baseRevenue),
            'additional_fees' => $this->moneyString($additionalFees),
            'late_fees' => $this->moneyString($lateFees),
            'damage_fees' => $this->moneyString($damageFees),
            'cleaning_fees' => $this->moneyString($cleaningFees),
            'other_fees' => $this->moneyString($otherFees),
            'total_collected' => $this->moneyString($collected),
            'total_outstanding' => $this->moneyString($outstanding),
            'deposits_received' => $this->moneyString($depositsReceived),
            'deposits_returned' => $this->moneyString($depositsReturned),
            'average_revenue_per_rental' => $this->moneyString($totalRentals > 0 ? $this->money($baseRevenue / $totalRentals) : 0),
            'unique_customers' => $uniqueCustomers,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $validRows
     * @return list<array<string, mixed>>
     */
    private function buildChart(Collection $validRows, Carbon $dateFrom, Carbon $dateTo): array
    {
        $days = max(1, (int) $dateFrom->diffInDays($dateTo) + 1);
        $grain = $days <= 45 ? 'day' : ($days <= 180 ? 'week' : 'month');

        $buckets = [];
        foreach ($validRows as $row) {
            $anchor = $row['rent_start_date'] ?? $row['booking_date'] ?? null;
            if ($anchor === null) {
                continue;
            }
            $date = Carbon::parse($anchor);
            $key = match ($grain) {
                'day' => $date->toDateString(),
                'week' => $date->copy()->startOfWeek()->toDateString(),
                default => $date->format('Y-m'),
            };
            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'period' => $key,
                    'grain' => $grain,
                    'rental_count' => 0,
                    'rental_revenue' => 0.0,
                    'additional_fees' => 0.0,
                ];
            }
            $buckets[$key]['rental_count']++;
            $buckets[$key]['rental_revenue'] = $this->money($buckets[$key]['rental_revenue'] + (float) $row['_base_revenue']);
            $buckets[$key]['additional_fees'] = $this->money($buckets[$key]['additional_fees'] + (float) $row['_additional_fees']);
        }

        ksort($buckets);

        return array_values(array_map(function (array $bucket): array {
            return [
                'period' => $bucket['period'],
                'grain' => $bucket['grain'],
                'rental_count' => $bucket['rental_count'],
                'rental_revenue' => $this->moneyString($bucket['rental_revenue']),
                'additional_fees' => $this->moneyString($bucket['additional_fees']),
            ];
        }, $buckets));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{data:list<array<string,mixed>>,meta:array<string,int>}
     */
    private function paginateRows(Collection $rows, int $page, int $perPage, bool $canViewCustomers): array
    {
        $total = $rows->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $data = $slice->map(function (array $row) use ($canViewCustomers): array {
            unset(
                $row['_sort_created_at'],
                $row['_sort_rent_start'],
                $row['_sort_rent_end'],
                $row['_sort_total'],
                $row['_base_revenue'],
                $row['_additional_fees'],
                $row['_late_fee'],
                $row['_damage_fee'],
                $row['_cleaning_fee'],
                $row['_other_fee'],
                $row['_collected'],
                $row['_outstanding'],
                $row['_deposits_received'],
                $row['_deposits_returned'],
                $row['_rental_days'],
                $row['share'],
            );

            if (! $canViewCustomers) {
                $row['customer_name'] = null;
                $row['customer_phone'] = null;
            }

            return $row;
        })->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRows(Collection $rows, string $sort, string $direction): Collection
    {
        $descending = strtolower($direction) !== 'asc';

        $sorted = match ($sort) {
            'created_at' => $rows->sortBy('_sort_created_at', descending: $descending),
            'rent_end_date' => $rows->sortBy('_sort_rent_end', descending: $descending),
            'invoice_number' => $rows->sortBy('invoice_number', descending: $descending),
            'total' => $rows->sortBy('_sort_total', descending: $descending),
            'status' => $rows->sortBy('rental_status', descending: $descending),
            default => $rows->sortBy('_sort_rent_start', descending: $descending),
        };

        return $sorted->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function buildJourney(Dress $dress, Collection $rows, string $order, bool $canViewCustomers): array
    {
        $events = [];

        $events[] = [
            'type' => 'product_created',
            'title' => 'إضافة الفستان للنظام',
            'occurred_at' => $dress->created_at?->toISOString(),
            'branch' => $dress->branch?->name,
            'customer' => null,
            'reference' => $dress->code,
            'user' => null,
            'amount' => null,
            'description' => 'تم إنشاء المنتج في الكتالوج',
            '_ts' => optional($dress->created_at)->timestamp ?? 0,
        ];

        $movements = InventoryMovement::query()
            ->where('dress_id', $dress->id)
            ->with(['fromBranch', 'toBranch'])
            ->orderBy('id')
            ->get();

        foreach ($movements as $movement) {
            $events[] = [
                'type' => (string) $movement->type,
                'title' => $this->movementTitle((string) $movement->type),
                'occurred_at' => $movement->created_at?->toISOString(),
                'branch' => $movement->toBranch?->name ?? $movement->fromBranch?->name,
                'customer' => null,
                'reference' => $movement->reference_id ? (string) $movement->reference_id : null,
                'user' => null,
                'amount' => null,
                'description' => $movement->notes ?? $movement->reason,
                'from_branch' => $movement->fromBranch?->name,
                'to_branch' => $movement->toBranch?->name,
                '_ts' => optional($movement->created_at)->timestamp ?? 0,
            ];
        }

        foreach ($rows as $row) {
            $events[] = [
                'type' => 'rental_invoice_created',
                'title' => $row['is_cancelled'] ? 'فاتورة إيجار ملغاة' : 'إنشاء فاتورة إيجار',
                'occurred_at' => isset($row['booking_date']) ? Carbon::parse($row['booking_date'])->toISOString() : null,
                'branch' => $row['branch_name'],
                'customer' => $canViewCustomers ? $row['customer_name'] : null,
                'reference' => $row['invoice_number'],
                'user' => $row['created_by']['name'] ?? null,
                'amount' => $row['base_rental_amount'],
                'description' => 'فاتورة إيجار مرتبطة بالفستان',
                '_ts' => $row['_sort_created_at'],
            ];

            if ($row['delivery_date']) {
                $events[] = [
                    'type' => 'delivered',
                    'title' => 'تسليم للعميل',
                    'occurred_at' => Carbon::parse($row['delivery_date'])->toISOString(),
                    'branch' => $row['branch_name'],
                    'customer' => $canViewCustomers ? $row['customer_name'] : null,
                    'reference' => $row['invoice_number'],
                    'user' => null,
                    'amount' => null,
                    'description' => 'تم تسليم الفستان',
                    '_ts' => Carbon::parse($row['delivery_date'])->timestamp,
                ];
            }

            if ($row['return_date']) {
                $events[] = [
                    'type' => 'returned',
                    'title' => 'إرجاع الفستان',
                    'occurred_at' => Carbon::parse($row['return_date'])->toISOString(),
                    'branch' => $row['branch_name'],
                    'customer' => $canViewCustomers ? $row['customer_name'] : null,
                    'reference' => $row['invoice_number'],
                    'user' => null,
                    'amount' => null,
                    'description' => $row['is_late'] ? 'إرجاع متأخر' : 'إرجاع',
                    '_ts' => Carbon::parse($row['return_date'])->timestamp,
                ];
            }

            if ((float) $row['_late_fee'] > 0) {
                $events[] = [
                    'type' => 'late_fee',
                    'title' => 'تسجيل رسوم تأخير',
                    'occurred_at' => $row['return_date'] ? Carbon::parse($row['return_date'])->toISOString() : null,
                    'branch' => $row['branch_name'],
                    'customer' => $canViewCustomers ? $row['customer_name'] : null,
                    'reference' => $row['invoice_number'],
                    'user' => null,
                    'amount' => $row['late_fee'],
                    'description' => 'رسوم تأخير من تسوية الإرجاع',
                    '_ts' => ($row['return_date'] ? Carbon::parse($row['return_date'])->timestamp : $row['_sort_created_at']) + 1,
                ];
            }

            if ((float) $row['_damage_fee'] > 0) {
                $events[] = [
                    'type' => 'damage_fee',
                    'title' => 'تسجيل رسوم تلف',
                    'occurred_at' => $row['return_date'] ? Carbon::parse($row['return_date'])->toISOString() : null,
                    'branch' => $row['branch_name'],
                    'customer' => $canViewCustomers ? $row['customer_name'] : null,
                    'reference' => $row['invoice_number'],
                    'user' => null,
                    'amount' => $row['damage_fee'],
                    'description' => 'رسوم تلف من تسوية الإرجاع',
                    '_ts' => ($row['return_date'] ? Carbon::parse($row['return_date'])->timestamp : $row['_sort_created_at']) + 2,
                ];
            }

            if ((float) $row['_cleaning_fee'] > 0) {
                $events[] = [
                    'type' => 'cleaning_fee',
                    'title' => 'تسجيل رسوم تنظيف',
                    'occurred_at' => $row['return_date'] ? Carbon::parse($row['return_date'])->toISOString() : null,
                    'branch' => $row['branch_name'],
                    'customer' => $canViewCustomers ? $row['customer_name'] : null,
                    'reference' => $row['invoice_number'],
                    'user' => null,
                    'amount' => $row['cleaning_fee'],
                    'description' => 'رسوم تنظيف من تسوية الإرجاع',
                    '_ts' => ($row['return_date'] ? Carbon::parse($row['return_date'])->timestamp : $row['_sort_created_at']) + 3,
                ];
            }

            if ((float) $row['_deposits_received'] > 0) {
                $events[] = [
                    'type' => 'deposit_received',
                    'title' => 'استلام تأمين',
                    'occurred_at' => isset($row['booking_date']) ? Carbon::parse($row['booking_date'])->toISOString() : null,
                    'branch' => $row['branch_name'],
                    'customer' => $canViewCustomers ? $row['customer_name'] : null,
                    'reference' => $row['invoice_number'],
                    'user' => null,
                    'amount' => $row['deposits_received'],
                    'description' => 'تأمين مستلم (ليس إيراداً)',
                    '_ts' => $row['_sort_created_at'] + 4,
                ];
            }

            if ((float) $row['_deposits_returned'] > 0) {
                $events[] = [
                    'type' => 'deposit_returned',
                    'title' => 'إرجاع تأمين',
                    'occurred_at' => $row['return_date'] ? Carbon::parse($row['return_date'])->toISOString() : null,
                    'branch' => $row['branch_name'],
                    'customer' => $canViewCustomers ? $row['customer_name'] : null,
                    'reference' => $row['invoice_number'],
                    'user' => null,
                    'amount' => $row['deposits_returned'],
                    'description' => 'تأمين مرتجع',
                    '_ts' => ($row['return_date'] ? Carbon::parse($row['return_date'])->timestamp : $row['_sort_created_at']) + 5,
                ];
            }

            if ((float) $row['_collected'] > 0) {
                $events[] = [
                    'type' => 'payment_recorded',
                    'title' => 'تحصيل مبلغ إيجار',
                    'occurred_at' => isset($row['booking_date']) ? Carbon::parse($row['booking_date'])->toISOString() : null,
                    'branch' => $row['branch_name'],
                    'customer' => $canViewCustomers ? $row['customer_name'] : null,
                    'reference' => $row['invoice_number'],
                    'user' => null,
                    'amount' => $row['collected'],
                    'description' => 'مبالغ محصلة منسوبة لهذا الفستان',
                    '_ts' => $row['_sort_created_at'] + 6,
                ];
            }
        }

        usort($events, function (array $a, array $b) use ($order): int {
            $cmp = ($a['_ts'] <=> $b['_ts']);

            return strtolower($order) === 'asc' ? $cmp : -$cmp;
        });

        return array_map(function (array $event): array {
            unset($event['_ts']);

            return $event;
        }, $events);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildTransfers(Dress $dress): array
    {
        return InventoryMovement::query()
            ->where('dress_id', $dress->id)
            ->where('type', InventoryMovement::TYPE_BRANCH_TRANSFER)
            ->with(['fromBranch', 'toBranch'])
            ->latest('id')
            ->get()
            ->map(function (InventoryMovement $movement): array {
                return [
                    'id' => (int) $movement->id,
                    'from_branch' => $movement->fromBranch ? [
                        'id' => (int) $movement->fromBranch->id,
                        'name' => $movement->fromBranch->name,
                    ] : null,
                    'to_branch' => $movement->toBranch ? [
                        'id' => (int) $movement->toBranch->id,
                        'name' => $movement->toBranch->name,
                    ] : null,
                    'transfer_date' => $movement->created_at?->toISOString(),
                    'status' => 'completed',
                    'requested_by' => $movement->created_by,
                    'approved_by' => null,
                    'received_by' => null,
                    'notes' => $movement->notes ?? $movement->reason,
                    'reference_number' => $movement->reference_id ? (string) $movement->reference_id : (string) $movement->id,
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $validRows
     * @return array<string, mixed>
     */
    private function buildCustomerInsights(Collection $validRows, bool $canViewCustomers): array
    {
        if (! $canViewCustomers) {
            return [
                'unique_customers' => $validRows->pluck('customer_id')->filter()->unique()->count(),
                'most_recent_customer' => null,
                'repeat_customers_count' => 0,
                'customers' => [],
                'customers_hidden' => true,
            ];
        }

        $grouped = $validRows
            ->filter(fn (array $row): bool => $row['customer_id'] !== null)
            ->groupBy('customer_id');

        $customers = $grouped->map(function (Collection $group) {
            /** @var array<string, mixed> $latest */
            $latest = $group->sortByDesc('_sort_rent_start')->first();

            return [
                'customer_id' => (int) $latest['customer_id'],
                'name' => $latest['customer_name'],
                'phone' => $latest['customer_phone'],
                'rentals_count' => $group->count(),
                'total_rental_value' => $this->moneyString($this->money((float) $group->sum('_base_revenue'))),
                'last_rental_date' => $latest['rent_start_date'] ?? $latest['booking_date'],
            ];
        })->sortByDesc('rentals_count')->values();

        $mostRecent = $validRows->sortByDesc('_sort_rent_start')->first();

        return [
            'unique_customers' => $customers->count(),
            'most_recent_customer' => $mostRecent ? [
                'customer_id' => $mostRecent['customer_id'],
                'name' => $mostRecent['customer_name'],
                'phone' => $mostRecent['customer_phone'],
            ] : null,
            'repeat_customers_count' => $customers->where('rentals_count', '>', 1)->count(),
            'customers' => $customers->all(),
            'customers_hidden' => false,
        ];
    }

    private function invoiceCollectedAmount(Invoice $invoice): float
    {
        return $this->money((float) $invoice->payments
            ->filter(function (InvoicePayment $payment): bool {
                return $payment->status === InvoicePayment::STATUS_PAID || $payment->status === null;
            })
            ->sum(fn (InvoicePayment $payment): float => (float) $payment->amount));
    }

    private function depositsReceivedAmount(Invoice $invoice, ?RentalReturnSettlement $settlement): float
    {
        $fromTransactions = $this->money((float) $invoice->securityDepositTransactions
            ->where('type', SecurityDepositTransaction::TYPE_COLLECTED)
            ->sum(fn (SecurityDepositTransaction $tx): float => (float) $tx->amount));

        if ($fromTransactions > 0) {
            return $fromTransactions;
        }

        if ($settlement !== null && (float) $settlement->deposit_paid_amount > 0) {
            return $this->money((float) $settlement->deposit_paid_amount);
        }

        return $this->money((float) ($invoice->deposit_paid_amount ?? 0));
    }

    private function depositsReturnedAmount(Invoice $invoice, ?RentalReturnSettlement $settlement): float
    {
        $fromTransactions = $this->money((float) $invoice->securityDepositTransactions
            ->where('type', SecurityDepositTransaction::TYPE_REFUNDED)
            ->sum(fn (SecurityDepositTransaction $tx): float => (float) $tx->amount));

        if ($fromTransactions > 0) {
            return $fromTransactions;
        }

        return $this->money((float) ($settlement?->deposit_refund_amount ?? 0));
    }

    private function rentalDays(Invoice $invoice): int
    {
        if ($invoice->rent_start_date && $invoice->rent_end_date) {
            return max(1, (int) Carbon::parse((string) $invoice->rent_start_date)
                ->diffInDays(Carbon::parse((string) $invoice->rent_end_date)) + 1);
        }

        return max(0, (int) ($invoice->days_of_rent ?? 0));
    }

    private function lineShare(float $lineTotal, float $itemsSubtotal): float
    {
        if ($itemsSubtotal <= 0) {
            return 1.0;
        }

        return $lineTotal / $itemsSubtotal;
    }

    private function allocate(float $amount, float $share): float
    {
        return $this->money($amount * $share);
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }

    private function moneyString(float $value): string
    {
        return number_format($this->money($value), 2, '.', '');
    }

    private function resolveDateFrom(Dress $dress, mixed $dateFrom): Carbon
    {
        if (is_string($dateFrom) && $dateFrom !== '') {
            return Carbon::parse($dateFrom)->startOfDay();
        }

        return $dress->created_at
            ? Carbon::parse($dress->created_at)->startOfDay()
            : Carbon::today()->startOfDay();
    }

    private function resolveDateTo(mixed $dateTo): Carbon
    {
        if (is_string($dateTo) && $dateTo !== '') {
            return Carbon::parse($dateTo)->endOfDay();
        }

        return Carbon::today()->endOfDay();
    }

    private function movementTitle(string $type): string
    {
        return match ($type) {
            InventoryMovement::TYPE_CREATED => 'إنشاء سجل المخزون',
            InventoryMovement::TYPE_STATUS_CHANGED => 'تغيير حالة الفستان',
            InventoryMovement::TYPE_MAINTENANCE => 'صيانة',
            InventoryMovement::TYPE_SOLD => 'بيع',
            InventoryMovement::TYPE_RENTED => 'تأجير من المخزون',
            InventoryMovement::TYPE_RETURNED => 'إرجاع للمخزون',
            InventoryMovement::TYPE_MANUAL_ADJUSTMENT => 'تعديل يدوي',
            InventoryMovement::TYPE_BRANCH_TRANSFER => 'نقل بين الفروع',
            default => 'حدث مخزون',
        };
    }

    private function userHasPermission(User $user, string $permissionKey): bool
    {
        if (method_exists($user, 'hasPermission')) {
            return (bool) $user->hasPermission($permissionKey);
        }

        $user->loadMissing('roles.permissions');

        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->key === $permissionKey) {
                    return true;
                }
            }
        }

        return false;
    }
}
