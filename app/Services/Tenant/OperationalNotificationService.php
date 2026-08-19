<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\Notification;
use App\Models\Tenant\RentalReturnSettlement;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Operational in-app reminders for atelier day-to-day workflows.
 */
class OperationalNotificationService
{
    public function __construct(private readonly TenantNotifier $notifier) {}

    public function invoiceCreated(Invoice $invoice): void
    {
        $invoice->loadMissing('customer');
        [$category, $typeLabel] = $this->typeMeta($invoice->type);
        $customer = $invoice->customer?->name ?? 'بدون عميل';
        $total = number_format((float) $invoice->total, 2);

        $this->safeBroadcast(
            title: "فاتورة {$typeLabel} جديدة #{$invoice->invoice_number}",
            message: "تم إنشاء فاتورة {$typeLabel} للعميل {$customer} بإجمالي {$total}. الحالة: {$invoice->status}.",
            category: $category,
            priority: 'normal',
            actionUrl: $this->invoiceUrl($invoice),
        );

        if ($invoice->delivery_date) {
            $this->safeBroadcast(
                title: "موعد تسليم مجدول #{$invoice->invoice_number}",
                message: "تم تحديد تاريخ التسليم: {$invoice->delivery_date} للعميل {$customer}.",
                category: 'delivery',
                priority: 'normal',
                actionUrl: $this->invoiceUrl($invoice),
            );
        }

        if ($invoice->type === Invoice::TYPE_RENT && ($invoice->rent_end_date || $invoice->return_date)) {
            $due = $invoice->return_date ?: $invoice->rent_end_date;
            $this->safeBroadcast(
                title: "موعد إرجاع مجدول #{$invoice->invoice_number}",
                message: "تم تحديد تاريخ الإرجاع المتوقع: {$due} للعميل {$customer}.",
                category: 'rental',
                priority: 'normal',
                actionUrl: $this->invoiceUrl($invoice),
            );
        }

        if ($invoice->type === Invoice::TYPE_TAILORING && $invoice->tailoring_due_date) {
            $this->safeBroadcast(
                title: "موعد تفصيل مجدول #{$invoice->invoice_number}",
                message: "تاريخ استحقاق التفصيل: {$invoice->tailoring_due_date} للعميل {$customer}.",
                category: 'tailoring',
                priority: 'normal',
                actionUrl: $this->invoiceUrl($invoice),
            );
        }
    }

    public function invoiceDelivered(Invoice $invoice): void
    {
        $invoice->loadMissing('customer');
        [, $typeLabel] = $this->typeMeta($invoice->type);
        $customer = $invoice->customer?->name ?? 'بدون عميل';

        $this->safeBroadcast(
            title: "تم التسليم #{$invoice->invoice_number}",
            message: "تم تسليم فاتورة {$typeLabel} للعميل {$customer}.",
            category: 'delivery',
            priority: 'normal',
            actionUrl: $this->invoiceUrl($invoice),
        );
    }

    public function invoiceReturned(Invoice $invoice): void
    {
        $invoice->loadMissing('customer');
        $customer = $invoice->customer?->name ?? 'بدون عميل';

        $this->safeBroadcast(
            title: "تم الإرجاع #{$invoice->invoice_number}",
            message: "تم تسجيل إرجاع إيجار للعميل {$customer}.",
            category: 'rental',
            priority: 'high',
            actionUrl: $this->invoiceUrl($invoice),
        );
    }

    public function rentalSettled(RentalReturnSettlement $settlement): void
    {
        $settlement->loadMissing('invoice.customer');
        $invoice = $settlement->invoice;
        if (! $invoice instanceof Invoice) {
            return;
        }

        $customer = $invoice->customer?->name ?? 'بدون عميل';
        $total = number_format((float) $settlement->settlement_total, 2);

        $this->safeBroadcast(
            title: "تسوية مرتجع #{$invoice->invoice_number}",
            message: "تمت تسوية إرجاع الإيجار للعميل {$customer}. إجمالي التسوية: {$total}.",
            category: 'rental',
            priority: 'high',
            actionUrl: $this->invoiceUrl($invoice),
        );
    }

    /**
     * Daily reminders for tomorrow's delivery / return / tailoring due dates.
     *
     * @return array{delivery:int,return:int,tailoring:int,overdue:int}
     */
    public function sendDueTomorrowReminders(?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::today())->startOfDay();
        $tomorrow = $today->copy()->addDay()->toDateString();

        $counts = ['delivery' => 0, 'return' => 0, 'tailoring' => 0, 'overdue' => 0];

        Invoice::query()
            ->with('customer')
            ->whereDate('delivery_date', $tomorrow)
            ->whereNotIn('status', [
                Invoice::STATUS_DELIVERED,
                Invoice::STATUS_RETURNED,
                Invoice::STATUS_CANCELLED,
                Invoice::STATUS_DRAFT,
            ])
            ->orderBy('id')
            ->each(function (Invoice $invoice) use (&$counts, $tomorrow): void {
                $title = "تذكير: تسليم غدًا #{$invoice->invoice_number}";
                if ($this->alreadySentToday($title)) {
                    return;
                }

                $customer = $invoice->customer?->name ?? 'بدون عميل';
                $this->safeBroadcast(
                    title: $title,
                    message: "موعد تسليم فاتورة العميل {$customer} غدًا ({$tomorrow}). جهّز الطلب قبل الموعد.",
                    category: 'delivery',
                    priority: 'urgent',
                    actionUrl: $this->invoiceUrl($invoice),
                );
                $counts['delivery']++;
            });

        Invoice::query()
            ->with('customer')
            ->where('type', Invoice::TYPE_RENT)
            ->where(function ($q) use ($tomorrow): void {
                $q->whereDate('return_date', $tomorrow)
                    ->orWhere(function ($q2) use ($tomorrow): void {
                        $q2->whereNull('return_date')->whereDate('rent_end_date', $tomorrow);
                    });
            })
            ->whereIn('status', [
                Invoice::STATUS_DELIVERED,
                Invoice::STATUS_PAID,
                Invoice::STATUS_PARTIALLY_PAID,
                Invoice::STATUS_CONFIRMED,
            ])
            ->whereNotIn('status', [Invoice::STATUS_RETURNED, Invoice::STATUS_CANCELLED])
            ->orderBy('id')
            ->each(function (Invoice $invoice) use (&$counts, $tomorrow): void {
                $title = "تذكير: إرجاع غدًا #{$invoice->invoice_number}";
                if ($this->alreadySentToday($title)) {
                    return;
                }

                $customer = $invoice->customer?->name ?? 'بدون عميل';
                $this->safeBroadcast(
                    title: $title,
                    message: "موعد إرجاع الإيجار للعميل {$customer} غدًا ({$tomorrow}). تواصل مع العميل للتأكيد.",
                    category: 'rental',
                    priority: 'urgent',
                    actionUrl: $this->invoiceUrl($invoice),
                );
                $counts['return']++;
            });

        Invoice::query()
            ->with('customer')
            ->where('type', Invoice::TYPE_TAILORING)
            ->whereDate('tailoring_due_date', $tomorrow)
            ->whereNotIn('status', [
                Invoice::STATUS_DELIVERED,
                Invoice::STATUS_RETURNED,
                Invoice::STATUS_CANCELLED,
                Invoice::STATUS_DRAFT,
            ])
            ->orderBy('id')
            ->each(function (Invoice $invoice) use (&$counts, $tomorrow): void {
                $title = "تذكير: استحقاق تفصيل غدًا #{$invoice->invoice_number}";
                if ($this->alreadySentToday($title)) {
                    return;
                }

                $customer = $invoice->customer?->name ?? 'بدون عميل';
                $this->safeBroadcast(
                    title: $title,
                    message: "موعد استحقاق التفصيل للعميل {$customer} غدًا ({$tomorrow}).",
                    category: 'tailoring',
                    priority: 'high',
                    actionUrl: $this->invoiceUrl($invoice),
                );
                $counts['tailoring']++;
            });

        Invoice::query()
            ->with('customer')
            ->where('type', Invoice::TYPE_RENT)
            ->where('status', Invoice::STATUS_DELIVERED)
            ->where(function ($q) use ($today): void {
                $q->whereDate('return_date', '<', $today->toDateString())
                    ->orWhere(function ($q2) use ($today): void {
                        $q2->whereNull('return_date')->whereDate('rent_end_date', '<', $today->toDateString());
                    });
            })
            ->orderBy('id')
            ->each(function (Invoice $invoice) use (&$counts): void {
                $title = "متأخر: إرجاع #{$invoice->invoice_number}";
                if ($this->alreadySentToday($title)) {
                    return;
                }

                $customer = $invoice->customer?->name ?? 'بدون عميل';
                $due = $invoice->return_date ?: $invoice->rent_end_date;
                $this->safeBroadcast(
                    title: $title,
                    message: "فاتورة إيجار العميل {$customer} متأخرة عن موعد الإرجاع ({$due}).",
                    category: 'rental',
                    priority: 'urgent',
                    actionUrl: $this->invoiceUrl($invoice),
                );
                $counts['overdue']++;
            });

        return $counts;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function typeMeta(string $type): array
    {
        return match ($type) {
            Invoice::TYPE_SELL => ['sales', 'بيع'],
            Invoice::TYPE_RENT => ['rental', 'إيجار'],
            Invoice::TYPE_TAILORING => ['tailoring', 'تفصيل'],
            default => ['system', 'تشغيل'],
        };
    }

    private function invoiceUrl(Invoice $invoice): string
    {
        return match ($invoice->type) {
            Invoice::TYPE_TAILORING => "/tailoring/orders/{$invoice->id}",
            default => "/orders/{$invoice->id}",
        };
    }

    private function alreadySentToday(string $title): bool
    {
        return Notification::query()
            ->whereDate('created_at', Carbon::today())
            ->where('title', $title)
            ->exists();
    }

    private function safeBroadcast(
        string $title,
        string $message,
        string $category,
        string $priority,
        ?string $actionUrl,
    ): void {
        try {
            $this->notifier->broadcast($title, $message, $category, $priority, $actionUrl);
        } catch (Throwable) {
            // Never break invoice/delivery flows because of notifications.
        }
    }
}
