<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Identity;

/**
 * Structured customer context for the sales agent / OpenAI payload.
 * Unknown fields must not be treated as a prompt to interrogate the customer.
 */
final class CustomerIdentityContextFormatter
{
    public function promptBlock(CustomerIdentity $identity): string
    {
        $name = $identity->trustedCustomerName() ?? 'unknown';
        $phone = $identity->phoneNumber ?? 'unknown';
        $business = $identity->businessName ?? 'unknown';
        $type = $identity->businessType ?? 'unknown';
        $city = $identity->city ?? 'unknown';
        $email = $identity->email ?? 'unknown';

        $confirmed = [];
        foreach ($identity->confirmed as $key => $value) {
            $confirmed[] = '- '.$key.' = '.$this->stringify($value);
        }
        $unknown = $identity->unknown;
        if ($unknown === []) {
            $unknown = ['none'];
        }

        $lines = [
            'Customer Profile:',
            'Name: '.$name,
            'WhatsApp: '.$phone,
            'Business: '.$business,
            'Business Type: '.$type,
            'City: '.$city,
            'Email: '.$email,
            'Name source: '.($identity->nameSource ?? 'unknown'),
            '',
            'Confirmed Facts:',
            $confirmed === [] ? '- none' : implode("\n", $confirmed),
            '',
            'Unknown:',
            implode("\n", array_map(static fn (string $field): string => '- '.$field, $unknown)),
            '',
            'Identity rules:',
            '- Never invent a customer name.',
            '- Never ask for information already known with sufficient confidence.',
            '- Unknown field does not mean you must ask about it now.',
            '- Prefer explicit customer-provided identity over platform display names.',
            '- Do not greet with a name unless it is trusted.',
            '- Do not expose internal IDs as the primary customer identity.',
        ];

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(CustomerIdentity $identity): array
    {
        return [
            'profile' => [
                'customer_id' => $identity->customerId,
                'name' => $identity->trustedCustomerName(),
                'whatsapp' => $identity->phoneNumber,
                'business' => $identity->businessName,
                'business_type' => $identity->businessType,
                'city' => $identity->city,
                'email' => $identity->email,
                'name_source' => $identity->nameSource,
                'name_confidence' => $identity->nameConfidence,
                'demo_tenant_id' => $identity->demoTenantId,
            ],
            'confirmed' => $identity->confirmed,
            'unknown' => $identity->unknown,
            'prompt' => $this->promptBlock($identity),
        ];
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }
}
