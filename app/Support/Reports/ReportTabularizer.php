<?php

namespace App\Support\Reports;

class ReportTabularizer
{
    /**
     * @return array{title: string, headers: list<string>, rows: list<list<string|int|float|null>>}
     */
    public static function fromReport(string $key, array $data): array
    {
        return match ($key) {
            'sales-daily' => self::dailyRows('تقرير المبيعات اليومية', $data),
            'sales-products' => self::productRows($data),
            'sales-employees' => self::employeeRows($data),
            'sales' => self::summaryRows('تقرير المبيعات', [
                'إجمالي المبيعات' => $data['total_sales'] ?? 0,
                'عدد الفواتير' => $data['invoices_count'] ?? 0,
                'متوسط الفاتورة' => $data['average_invoice_value'] ?? 0,
            ]),
            'tailoring' => self::summaryRows('تقرير التفصيل', [
                'إجمالي الطلبات' => $data['total_orders'] ?? 0,
                'طلبات جاهزة' => $data['ready_orders'] ?? 0,
                'طلبات متأخرة' => $data['late_orders'] ?? 0,
                'قيد التنفيذ' => $data['in_progress_orders'] ?? 0,
                'إجمالي الإيراد' => $data['total_revenue'] ?? 0,
            ]),
            'rental' => self::summaryRows('تقرير الإيجار', [
                'إجمالي الطلبات' => $data['total'] ?? 0,
                'نشطة' => $data['active'] ?? 0,
                'مرتجعة' => $data['returned'] ?? 0,
                'متأخرة' => $data['overdue'] ?? 0,
                'الإيراد' => $data['revenue'] ?? 0,
                'المحصّل' => $data['collected'] ?? 0,
                'المتبقي' => $data['remaining'] ?? 0,
            ]),
            'customers' => self::summaryRows('تقرير العملاء', [
                'إجمالي العملاء' => $data['total'] ?? 0,
                'عملاء VIP' => $data['vip'] ?? 0,
                'جدد هذا الشهر' => $data['new_this_month'] ?? 0,
                'إجمالي المبيعات' => $data['total_sales'] ?? 0,
            ]),
            'expenses' => self::expenseRows($data),
            'cash' => self::summaryRows('تقرير الصندوق', [
                'إجمالي الداخل' => $data['total_in'] ?? 0,
                'إجمالي الخارج' => $data['total_out'] ?? 0,
                'الصافي' => $data['net'] ?? 0,
            ]),
            'accounting' => self::accountingRows($data),
            'deliveries' => self::summaryRows('تقرير التسليم', self::flattenStatsArabic($data)),
            'returns' => self::summaryRows('تقرير المرتجعات', self::flattenStatsArabic($data)),
            'payments' => self::summaryRows('تقرير المدفوعات', self::flattenStatsArabic($data)),
            'suppliers' => self::supplierRows($data),
            'inventory' => self::inventoryRows($data),
            default => self::summaryRows('تقرير', self::flattenStatsArabic($data)),
        };
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{title: string, headers: list<string>, rows: list<list<string|int|float|null>>}
     */
    private static function summaryRows(string $title, array $metrics): array
    {
        $rows = [];
        foreach ($metrics as $label => $value) {
            $rows[] = [(string) $label, is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)];
        }

        return [
            'title' => $title,
            'headers' => ['البند', 'القيمة'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @return array{title: string, headers: list<string>, rows: list<list<string|int|float|null>>}
     */
    private static function dailyRows(string $title, array $data): array
    {
        $rows = array_map(static fn (array $row): array => [
            $row['date'] ?? '',
            $row['invoices_count'] ?? 0,
            $row['total'] ?? 0,
        ], $data);

        return [
            'title' => $title,
            'headers' => ['التاريخ', 'عدد الفواتير', 'الإجمالي'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $data
     */
    private static function productRows(array $data): array
    {
        $rows = array_map(static fn (array $row): array => [
            $row['product_name'] ?? '',
            $row['product_code'] ?? '',
            $row['quantity_sold'] ?? 0,
            $row['revenue'] ?? 0,
        ], $data);

        return [
            'title' => 'المبيعات حسب المنتج',
            'headers' => ['المنتج', 'الكود', 'الكمية', 'الإيراد'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $data
     */
    private static function employeeRows(array $data): array
    {
        $rows = array_map(static fn (array $row): array => [
            $row['employee_name'] ?? '',
            $row['invoices_count'] ?? 0,
            $row['total_sales'] ?? 0,
        ], $data);

        return [
            'title' => 'المبيعات حسب الموظف',
            'headers' => ['الموظف', 'عدد الفواتير', 'إجمالي المبيعات'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function expenseRows(array $data): array
    {
        $rows = [
            ['إجمالي المبلغ', $data['total_amount'] ?? 0],
            ['معلق', $data['pending_amount'] ?? 0],
            ['معتمد', $data['approved_amount'] ?? 0],
            ['مدفوع', $data['paid_amount'] ?? 0],
            ['ملغى', $data['cancelled_amount'] ?? 0],
        ];

        foreach ($data['by_category'] ?? [] as $category) {
            $rows[] = [
                'فئة #'.($category['expense_category_id'] ?? '-'),
                $category['total_amount'] ?? 0,
            ];
        }

        return [
            'title' => 'تقرير المصروفات',
            'headers' => ['البند / الفئة', 'المبلغ'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function accountingRows(array $data): array
    {
        $rows = [
            ['إجمالي الدخل', $data['total_income'] ?? 0],
            ['إجمالي المصروفات', $data['total_expenses'] ?? 0],
            ['صافي الربح', $data['net_profit'] ?? $data['net_income'] ?? $data['net_change'] ?? 0],
        ];

        foreach ($data['cashbox_balances'] ?? [] as $cashbox) {
            $rows[] = ['صندوق: '.($cashbox['name'] ?? '-'), $cashbox['balance'] ?? 0];
        }

        return [
            'title' => 'تقرير المحاسبة',
            'headers' => ['البند', 'المبلغ'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function supplierRows(array $data): array
    {
        $rows = array_map(static fn (array $row): array => [
            $row['name'] ?? '',
            $row['orders_count'] ?? 0,
            $row['total_purchases'] ?? 0,
            $row['total_paid'] ?? 0,
            $row['balance'] ?? 0,
        ], $data['suppliers'] ?? []);

        return [
            'title' => 'تقرير الموردين',
            'headers' => ['المورد', 'الطلبات', 'المشتريات', 'المدفوع', 'الرصيد'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function inventoryRows(array $data): array
    {
        $rows = [
            ['إجمالي الفساتين', $data['total_dresses'] ?? 0],
            ['متاح', $data['available'] ?? 0],
            ['مؤجّر', $data['rented'] ?? 0],
            ['مباع', $data['sold'] ?? 0],
            ['نسبة الاستخدام %', $data['utilization_percent'] ?? 0],
        ];

        foreach ($data['by_status'] ?? [] as $status => $count) {
            $rows[] = ['الحالة: '.$status, $count];
        }

        return [
            'title' => 'تقرير المخزون',
            'headers' => ['البند', 'القيمة'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function flattenStatsArabic(array $data): array
    {
        $labels = [
            'total' => 'الإجمالي',
            'count' => 'العدد',
            'amount' => 'المبلغ',
            'paid' => 'المدفوع',
            'remaining' => 'المتبقي',
            'pending' => 'معلق',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغى',
            'active' => 'نشط',
            'returned' => 'مرتجع',
            'overdue' => 'متأخر',
            'revenue' => 'الإيراد',
            'collected' => 'المحصّل',
        ];

        $flat = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $label = ($labels[(string) $key] ?? (string) $key).' / '.($labels[(string) $subKey] ?? (string) $subKey);
                    $flat[$label] = $subValue;
                }

                continue;
            }
            $flat[$labels[(string) $key] ?? (string) $key] = $value;
        }

        return $flat;
    }
}
