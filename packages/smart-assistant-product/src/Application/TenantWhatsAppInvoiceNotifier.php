<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use App\Models\Tenant\Invoice;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantWaDispatch;
use DressnMore\SmartAssistantProduct\SalesIntelligence\Orchestrator\Application\TenantIdentityResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Customer WhatsApp notifications for confirmed invoices, pickups, returns.
 * Tenant session only. Text and PDF are tracked separately so a failed PDF
 * can be retried after the confirmation text already went out.
 */
final class TenantWhatsAppInvoiceNotifier
{
    public function __construct(
        private readonly TenantWhatsAppOutbound $outbound,
        private readonly TenantInvoicePdf $pdf,
        private readonly TenantAtelierKnowledge $atelierKnowledge,
        private readonly TenantIdentityResolver $identityResolver,
    ) {}

    public function notifyConfirmed(Tenant $tenant, Invoice $invoice, ?string $preferredTo = null, bool $includeText = true): bool
    {
        $ready = $this->prepare($tenant, $invoice, $preferredTo);
        if ($ready === null) {
            return false;
        }
        [$to, $business] = $ready;

        $ok = false;
        if ($includeText) {
            $ok = $this->sendClaimed(
                $tenant,
                $invoice,
                SmartAssistantWaDispatch::KIND_INVOICE_CONFIRMED,
                $to,
                fn () => $this->outbound->sendText((string) $tenant->id, $to, $this->messageFor(
                    SmartAssistantWaDispatch::KIND_INVOICE_CONFIRMED,
                    $invoice,
                    $business,
                )),
            ) || $ok;
        }

        return $this->sendPdf($tenant, $invoice, $to, $business, false) || $ok;
    }

    /**
     * Manual staff button: send PDF now if the atelier WhatsApp session is live.
     *
     * @return array{sent:bool, fallback:bool, reason:?string}
     */
    public function sendNow(Tenant $tenant, Invoice $invoice): array
    {
        $tenantId = (int) $tenant->id;
        if ($this->outbound->isPlatformTenant($tenantId)) {
            return ['sent' => false, 'fallback' => true, 'reason' => 'not_connected'];
        }
        if (! $this->outbound->sessionConnected((string) $tenantId)) {
            return ['sent' => false, 'fallback' => true, 'reason' => 'not_connected'];
        }

        $to = $this->outbound->resolveRecipient($tenant, $invoice, null);
        if ($to === null || $to === '') {
            return ['sent' => false, 'fallback' => true, 'reason' => 'no_recipient'];
        }

        $identity = $this->identityResolver->resolve($tenantId);
        $business = $identity->businessName !== '' ? $identity->businessName : (string) ($tenant->name ?: 'الأتيليه');

        $ok = $this->sendPdf($tenant, $invoice, $to, $business, true);

        return $ok
            ? ['sent' => true, 'fallback' => false, 'reason' => null]
            : ['sent' => false, 'fallback' => true, 'reason' => 'send_failed'];
    }

    public function notifyPickupReminder(Tenant $tenant, Invoice $invoice): bool
    {
        return $this->dispatchSimple($tenant, $invoice, SmartAssistantWaDispatch::KIND_PICKUP_REMINDER);
    }

    public function notifyReturnReminder(Tenant $tenant, Invoice $invoice): bool
    {
        return $this->dispatchSimple($tenant, $invoice, SmartAssistantWaDispatch::KIND_RETURN_REMINDER);
    }

    public function notifyReturnCongrats(Tenant $tenant, Invoice $invoice): bool
    {
        return $this->dispatchSimple($tenant, $invoice, SmartAssistantWaDispatch::KIND_RETURN_CONGRATS);
    }

    /**
     * @return array{0:string,1:string}|null [to, business]
     */
    private function prepare(Tenant $tenant, Invoice $invoice, ?string $preferredTo): ?array
    {
        $tenantId = (int) $tenant->id;
        if ($this->outbound->isPlatformTenant($tenantId)) {
            return null;
        }
        if (! $this->outbound->sessionConnected((string) $tenantId)) {
            return null;
        }

        $to = $this->outbound->resolveRecipient($tenant, $invoice, $preferredTo);
        if ($to === null || $to === '') {
            return null;
        }

        $identity = $this->identityResolver->resolve($tenantId);
        $business = $identity->businessName !== '' ? $identity->businessName : (string) ($tenant->name ?: 'الأتيليه');

        return [$to, $business];
    }

    private function dispatchSimple(Tenant $tenant, Invoice $invoice, string $kind): bool
    {
        $ready = $this->prepare($tenant, $invoice, null);
        if ($ready === null) {
            return false;
        }
        [$to, $business] = $ready;

        return $this->sendClaimed(
            $tenant,
            $invoice,
            $kind,
            $to,
            fn () => $this->outbound->sendText((string) $tenant->id, $to, $this->messageFor($kind, $invoice, $business)),
        );
    }

    private function sendPdf(Tenant $tenant, Invoice $invoice, string $to, string $business, bool $force): bool
    {
        $send = function () use ($tenant, $invoice, $to, $business): void {
            $bytes = $this->pdf->render($invoice, $business);
            if ($bytes === '') {
                throw new \RuntimeException('empty invoice pdf');
            }
            $this->outbound->sendDocument(
                (string) $tenant->id,
                $to,
                'invoice-'.$invoice->invoice_number.'.pdf',
                $bytes,
                'فاتورة '.$invoice->invoice_number,
            );
        };

        if ($force) {
            try {
                $send();

                return true;
            } catch (Throwable $e) {
                Log::warning('whatsapp.invoice_pdf_failed', [
                    'tenant_id' => $tenant->id,
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        return $this->sendClaimed($tenant, $invoice, SmartAssistantWaDispatch::KIND_INVOICE_PDF, $to, $send);
    }

    private function sendClaimed(Tenant $tenant, Invoice $invoice, string $kind, string $to, callable $send): bool
    {
        $row = $this->claim((int) $tenant->id, (int) $invoice->id, $kind, $to);
        if ($row === null) {
            return false;
        }

        try {
            $send();

            return true;
        } catch (Throwable $e) {
            $row->delete();
            Log::warning('whatsapp.tenant_dispatch_failed', [
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'kind' => $kind,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function claim(int $tenantId, int $invoiceId, string $kind, string $to): ?SmartAssistantWaDispatch
    {
        try {
            return SmartAssistantWaDispatch::query()->create([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoiceId,
                'kind' => $kind,
                'to_phone' => mb_substr($to, 0, 80),
                'sent_at' => now(),
            ]);
        } catch (QueryException) {
            return null;
        }
    }

    private function messageFor(string $kind, Invoice $invoice, string $business): string
    {
        $invoice->loadMissing(['customer', 'branch']);
        $name = (string) ($invoice->customer?->name ?: '');
        $hello = $name !== '' ? $name : 'عميلنا العزيز';
        $number = (string) $invoice->invoice_number;
        $branchLine = $this->branchLine($invoice);

        return match ($kind) {
            SmartAssistantWaDispatch::KIND_INVOICE_CONFIRMED =>
                "تم تأكيد حجزك بنجاح ✅\n"
                ."رقم الفاتورة: {$number}\n"
                .'الإجمالي: '.number_format((float) $invoice->total, 2)."\n"
                .($this->pickupLabel($invoice) !== null ? $this->pickupLabel($invoice)."\n" : '')
                .($this->returnLabel($invoice) !== null ? $this->returnLabel($invoice)."\n" : '')
                .$branchLine
                ."مرفق فاتورة PDF. شكرًا لثقتك في {$business} 🌸",
            SmartAssistantWaDispatch::KIND_PICKUP_REMINDER =>
                "تذكير بالموعد 👗\n"
                ."{$hello}، استلام طلبك #{$number} غدًا"
                .($this->pickupLabel($invoice) !== null ? "\n".$this->pickupLabel($invoice) : '')
                ."\n".$branchLine
                .'ننتظرك في '.$business.' 💛',
            SmartAssistantWaDispatch::KIND_RETURN_REMINDER =>
                "تذكير بالإرجاع ⏰\n"
                ."{$hello}، موعد إرجاع الطلب #{$number} غدًا"
                .($this->returnLabel($invoice) !== null ? "\n".$this->returnLabel($invoice) : '')
                ."\n".$branchLine
                .'شكرًا لتعاونك مع '.$business.'.',
            SmartAssistantWaDispatch::KIND_RETURN_CONGRATS =>
                "ألف مبروك 🎉\n"
                ."{$hello}، نتمنى لكِ يومًا سعيدًا بعد مناسبة طلب #{$number}.\n"
                ."سعدنا بخدمتك في {$business} — ننتظرك في الزيارة الجاية 🌸",
            default => "رسالة من {$business} بخصوص الفاتورة {$number}.",
        };
    }

    private function pickupLabel(Invoice $invoice): ?string
    {
        $date = $invoice->delivery_date ?: $invoice->rent_start_date;
        if ($date === null) {
            return null;
        }

        return 'موعد الاستلام: '.$date->toDateString();
    }

    private function returnLabel(Invoice $invoice): ?string
    {
        $date = $invoice->return_date ?: $invoice->rent_end_date;
        if ($date === null) {
            return null;
        }

        return 'موعد الإرجاع: '.$date->toDateString();
    }

    private function branchLine(Invoice $invoice): string
    {
        $invoice->loadMissing('branch');
        if ($invoice->branch === null) {
            $branches = $this->atelierKnowledge->branches();
            if ($branches === []) {
                return '';
            }
            $b = $branches[0];
            $line = 'الفرع: '.(string) $b['name'];
            if (! empty($b['address'])) {
                $line .= ' — '.(string) $b['address'];
            }

            return $line."\n";
        }

        $parts = array_values(array_filter([
            (string) $invoice->branch->name,
            trim((string) ($invoice->branch->address ?? '')),
            trim((string) ($invoice->branch->phone ?? '')),
        ], static fn (string $p): bool => $p !== ''));

        return $parts !== [] ? 'الفرع: '.implode(' — ', $parts)."\n" : '';
    }
}
