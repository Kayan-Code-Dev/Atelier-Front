<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Response\Contracts\LocalizationInterface;
use DressnMore\Aos\Response\Domain\Aggregator\ToolOutcome;
use DressnMore\Aos\Response\Domain\Policy\ResponsePolicy;

/**
 * Formats a single successful tool outcome into a localized sentence.
 */
final class ResultFormatter
{
    public function __construct(
        private readonly LocalizationInterface $i18n,
        private readonly ResponsePolicy $policy = new ResponsePolicy(),
    ) {}

    public function format(ToolOutcome $outcome, string $locale): string
    {
        $i18n = $this->i18n->withLocale($locale);
        $payload = $this->policy->filterPayload($outcome->payload());
        $key = $outcome->toolName().'.success';

        $replacements = match ($outcome->toolName()) {
            'CreateCustomer', 'SearchCustomer' => [
                'name' => (string) ($payload['name'] ?? $payload['customer_name'] ?? $payload['customerName'] ?? '—'),
            ],
            'CreateReservation' => [
                'day' => (string) ($payload['day'] ?? $payload['date_label'] ?? $payload['date'] ?? '—'),
                'time' => (string) ($payload['time'] ?? $payload['time_label'] ?? '—'),
            ],
            'GenerateReport' => [
                'amount' => $this->formatNumber($payload['amount'] ?? $payload['total'] ?? $payload['sales'] ?? 0, $locale),
                'count' => (string) ($payload['count'] ?? $payload['bookings'] ?? $payload['reservations'] ?? 0),
            ],
            'CreateInvoice' => [
                'invoice' => (string) ($payload['invoice'] ?? $payload['invoice_number'] ?? $payload['id'] ?? '—'),
            ],
            default => [],
        };

        $translated = $i18n->translate($key, $replacements, $locale);
        if ($translated === $key) {
            return $i18n->translate('generic_success', [], $locale);
        }

        return $translated;
    }

    private function formatNumber(mixed $value, string $locale): string
    {
        $n = is_numeric($value) ? (float) $value : 0.0;
        $formatted = number_format($n, 0, '.', ',');

        return $locale === 'ar' ? str_replace(',', '٬', $formatted) : $formatted;
    }
}
