<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency log for tenant WhatsApp invoice/reminder messages.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $invoice_id
 * @property string $kind
 * @property string|null $to_phone
 */
final class SmartAssistantWaDispatch extends Model
{
    public const KIND_INVOICE_CONFIRMED = 'invoice_confirmed';
    public const KIND_INVOICE_PDF = 'invoice_pdf';
    public const KIND_PICKUP_REMINDER = 'pickup_reminder';
    public const KIND_RETURN_REMINDER = 'return_reminder';
    public const KIND_RETURN_CONGRATS = 'return_congrats';

    protected $connection = 'central';

    protected $table = 'smart_assistant_wa_dispatches';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'kind',
        'to_phone',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
