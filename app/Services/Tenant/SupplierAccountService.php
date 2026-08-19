<?php

namespace App\Services\Tenant;

use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\SupplierPayment;

class SupplierAccountService
{
    public function __construct(private readonly SupplierService $supplierService) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(Supplier $supplier): array
    {
        $supplier = $this->supplierService->recalculateCurrentBalance($supplier);

        $orders = PurchaseOrder::query()
            ->with(['items', 'branch'])
            ->where('supplier_id', $supplier->id)
            ->where('status', '!=', PurchaseOrder::STATUS_CANCELLED)
            ->latest('order_date')
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (PurchaseOrder $order): array => [
                'id' => $order->id,
                'purchase_order_number' => $order->purchase_order_number,
                'supplier' => $supplier->name,
                'supplier_id' => $supplier->id,
                'branch_id' => $order->branch_id,
                'branch_name' => $order->branch?->name,
                'status' => $order->received_at && ! in_array($order->status, [PurchaseOrder::STATUS_PAID, PurchaseOrder::STATUS_PARTIALLY_PAID], true)
                    ? 'received'
                    : $order->status,
                'total' => (float) $order->total,
                'paid_amount' => (float) $order->paid_amount,
                'deposit_amount' => (float) $order->deposit_amount,
                'remaining_amount' => (float) $order->remaining_amount,
                'order_date' => $order->order_date?->toDateString() ?? '',
                'received_at' => $order->received_at,
                'inventory_received' => $order->received_at !== null,
                'items' => $order->items->map(static fn ($item): array => [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $payments = SupplierPayment::query()
            ->with('purchaseOrder')
            ->where('supplier_id', $supplier->id)
            ->latest('paid_at')
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (SupplierPayment $payment): array => [
                'id' => $payment->id,
                'supplier' => $supplier->name,
                'purchase_order_id' => $payment->purchase_order_id,
                'purchase_order_number' => $payment->purchaseOrder?->purchase_order_number ?? '—',
                'amount' => (float) $payment->amount,
                'method' => $payment->method ?? 'cash',
                'reference' => $payment->reference ?? '',
                'paid_at' => $payment->paid_at?->toDateString() ?? '',
                'notes' => $payment->notes ?? '',
            ])
            ->values()
            ->all();

        $returns = PurchaseOrder::query()
            ->where('supplier_id', $supplier->id)
            ->where('is_returned', true)
            ->latest('returned_at')
            ->get()
            ->map(fn (PurchaseOrder $order): array => [
                'id' => $order->id,
                'return_number' => 'RET-'.$order->purchase_order_number,
                'date' => $order->returned_at?->toDateString() ?? '',
                'amount' => (float) $order->total,
                'reason' => $order->return_notes ?? '',
            ])
            ->values()
            ->all();

        $statement = $this->buildStatement(
            openingBalance: (float) ($supplier->opening_balance ?? 0),
            orders: $orders,
            payments: $payments,
            returns: $returns,
        );

        return [
            'supplier' => [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'name' => $supplier->name,
                'current_balance' => (float) $supplier->current_balance,
                'opening_balance' => (float) ($supplier->opening_balance ?? 0),
                'status' => $supplier->status,
                'orders_count' => count($orders),
                'total_purchases' => (float) ($supplier->total_purchases ?? 0),
                'total_paid' => (float) ($supplier->total_paid ?? 0),
                'total_remaining' => (float) ($supplier->total_remaining ?? 0),
                'remaining' => (float) ($supplier->total_remaining ?? 0),
            ],
            'purchase_orders' => $orders,
            'payments' => $payments,
            'returns' => $returns,
            'statement' => $statement,
        ];
    }

    public function findSupplierOrFail(int $supplierId): Supplier
    {
        return $this->supplierService->findOrFail($supplierId);
    }

    /**
     * @param  list<array<string, mixed>>  $orders
     * @param  list<array<string, mixed>>  $payments
     * @param  list<array<string, mixed>>  $returns
     * @return list<array<string, mixed>>
     */
    private function buildStatement(float $openingBalance, array $orders, array $payments, array $returns): array
    {
        $lines = [];

        if (abs($openingBalance) > 0.00001) {
            $lines[] = [
                'sort_at' => '0000-00-00',
                'date' => '',
                'description' => 'رصيد افتتاحي',
                'debit' => $openingBalance > 0 ? $openingBalance : 0.0,
                'credit' => $openingBalance < 0 ? abs($openingBalance) : 0.0,
            ];
        }

        foreach ($orders as $order) {
            $lines[] = [
                'sort_at' => $order['order_date'] ?: '9999-12-31',
                'date' => $order['order_date'],
                'description' => 'طلبية شراء '.$order['purchase_order_number'],
                'debit' => (float) $order['total'],
                'credit' => 0.0,
            ];
        }

        foreach ($payments as $payment) {
            $label = str_starts_with((string) ($payment['reference'] ?? ''), 'DEP-')
                ? 'عربون / دفعة'
                : 'دفعة مورد';
            $lines[] = [
                'sort_at' => $payment['paid_at'] ?: '9999-12-31',
                'date' => $payment['paid_at'],
                'description' => trim($label.' '.($payment['reference'] ?? '')),
                'debit' => 0.0,
                'credit' => (float) $payment['amount'],
            ];
        }

        foreach ($returns as $return) {
            $lines[] = [
                'sort_at' => $return['date'] ?: '9999-12-31',
                'date' => $return['date'],
                'description' => 'مرتجع '.$return['return_number'],
                'debit' => 0.0,
                'credit' => (float) $return['amount'],
            ];
        }

        usort($lines, fn (array $a, array $b): int => strcmp((string) $a['sort_at'], (string) $b['sort_at']));

        $balance = 0.0;
        $statement = [];

        foreach ($lines as $index => $line) {
            $balance += (float) $line['debit'] - (float) $line['credit'];
            $statement[] = [
                'id' => $index + 1,
                'date' => $line['date'],
                'description' => $line['description'],
                'debit' => round((float) $line['debit'], 2),
                'credit' => round((float) $line['credit'], 2),
                'balance' => round($balance, 2),
            ];
        }

        return $statement;
    }
}
