<?php

namespace App\Accounting;

use App\Models\Tenant\JournalEntry;

class JournalSourcePresenter
{
    /**
     * @return array{source_type: string, source_id: int|null, source_label: string, source_url: string|null, source_reference: string|null}
     */
    public static function present(JournalEntry $entry): array
    {
        $type = (string) ($entry->source_type ?: JournalEntry::SOURCE_MANUAL);
        $id = $entry->source_id ? (int) $entry->source_id : null;
        $reference = $entry->source_reference ?: $entry->reference_number;
        $label = self::label($type, $reference, $id);

        return [
            'source_type' => $type,
            'source_id' => $id,
            'source_label' => $label,
            'source_url' => self::url($type, $id),
            'source_reference' => $reference,
        ];
    }

    public static function label(string $type, ?string $reference, ?int $id): string
    {
        $name = match ($type) {
            JournalEntry::SOURCE_MANUAL => 'قيد يدوي',
            JournalEntry::SOURCE_INVOICE, JournalEntry::SOURCE_SALE => 'فاتورة بيع',
            JournalEntry::SOURCE_PAYMENT => 'دفعة',
            JournalEntry::SOURCE_EXPENSE => 'مصروف',
            JournalEntry::SOURCE_RETURN, JournalEntry::SOURCE_RENTAL_RETURN_SETTLEMENT => 'مرتجع',
            JournalEntry::SOURCE_PURCHASE_ORDER, JournalEntry::SOURCE_PURCHASE => 'أمر شراء',
            JournalEntry::SOURCE_SUPPLIER_PAYMENT => 'دفعة مورد',
            JournalEntry::SOURCE_CASH_MOVEMENT, JournalEntry::SOURCE_TREASURY => 'حركة خزنة',
            JournalEntry::SOURCE_RESERVATION => 'حجز',
            JournalEntry::SOURCE_PAYROLL => 'رواتب',
            JournalEntry::SOURCE_ASSET, JournalEntry::SOURCE_FIXED_ASSET => 'أصل ثابت',
            JournalEntry::SOURCE_ASSET_DISPOSAL => 'تصرف في أصل',
            JournalEntry::SOURCE_DEPRECIATION => 'إهلاك',
            JournalEntry::SOURCE_EQUITY => 'حقوق ملكية',
            JournalEntry::SOURCE_LOAN => 'قرض',
            JournalEntry::SOURCE_LOAN_SETTLEMENT => 'سداد قرض',
            JournalEntry::SOURCE_OPENING_BALANCE => 'رصيد افتتاحي',
            JournalEntry::SOURCE_ADJUSTMENT => 'تسوية',
            JournalEntry::SOURCE_BANK_RECONCILIATION => 'تسوية بنكية',
            JournalEntry::SOURCE_REVERSAL => 'قيد عكسي',
            JournalEntry::SOURCE_SECURITY_DEPOSIT_COLLECTION => 'تأمين',
            default => 'حركة محاسبية',
        };

        if ($reference) {
            return $name.' #'.$reference;
        }

        if ($id) {
            return $name.' #'.$id;
        }

        return $name;
    }

    public static function url(string $type, ?int $id): ?string
    {
        if ($id === null) {
            return match ($type) {
                JournalEntry::SOURCE_OPENING_BALANCE => '/accounting/opening-balances',
                JournalEntry::SOURCE_CASH_MOVEMENT, JournalEntry::SOURCE_TREASURY => '/accounting/treasury',
                default => null,
            };
        }

        return match ($type) {
            JournalEntry::SOURCE_INVOICE, JournalEntry::SOURCE_SALE => '/sales/invoices/'.$id,
            JournalEntry::SOURCE_PAYMENT => '/payments/'.$id,
            JournalEntry::SOURCE_EXPENSE => '/expenses/'.$id,
            JournalEntry::SOURCE_PURCHASE_ORDER, JournalEntry::SOURCE_PURCHASE => '/purchase-orders/'.$id,
            JournalEntry::SOURCE_SUPPLIER_PAYMENT => '/supplier-payments/'.$id,
            JournalEntry::SOURCE_CASH_MOVEMENT, JournalEntry::SOURCE_TREASURY => '/cashboxes',
            JournalEntry::SOURCE_RETURN, JournalEntry::SOURCE_RENTAL_RETURN_SETTLEMENT => '/returns/'.$id,
            JournalEntry::SOURCE_RESERVATION => '/reservations/'.$id,
            JournalEntry::SOURCE_OPENING_BALANCE => '/accounting/opening-balances',
            JournalEntry::SOURCE_ASSET, JournalEntry::SOURCE_FIXED_ASSET, JournalEntry::SOURCE_ASSET_DISPOSAL => '/accounting/assets/'.$id,
            JournalEntry::SOURCE_DEPRECIATION => '/accounting/assets/depreciation',
            JournalEntry::SOURCE_EQUITY => '/accounting/equity',
            JournalEntry::SOURCE_LOAN, JournalEntry::SOURCE_LOAN_SETTLEMENT => '/accounting/liabilities',
            JournalEntry::SOURCE_BANK_RECONCILIATION => '/accounting/reconciliation',
            default => null,
        };
    }
}
