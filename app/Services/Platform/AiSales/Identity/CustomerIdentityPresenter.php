<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Identity;

use App\Models\Central\CrmLead;

final class CustomerIdentityPresenter
{
    public function __construct(
        private readonly CustomerNameExtractor $names = new CustomerNameExtractor(),
    ) {}

    public function displayName(?CrmLead $lead, ?string $fallbackPhone = null): string
    {
        $identity = $this->fromLead($lead, $fallbackPhone);
        $name = $identity->trustedCustomerName();
        if ($name !== null) {
            return $name;
        }
        $phone = PhoneIdentity::display($identity->phoneNumber ?? $fallbackPhone);
        if ($phone !== null) {
            return $phone;
        }

        return 'WhatsApp contact';
    }

    /**
     * @return array{name:string,phone:?string,label:string}
     */
    public function adminLabel(?CrmLead $lead, ?string $fallbackPhone = null): array
    {
        $identity = $this->fromLead($lead, $fallbackPhone);
        $phone = PhoneIdentity::display($identity->phoneNumber ?? $fallbackPhone);
        $name = $identity->trustedCustomerName();
        if ($name === null && $lead?->name && ! CustomerNameExtractor::isPlaceholder((string) $lead->name)) {
            $name = trim((string) $lead->name);
        }

        return [
            'name' => $name ?: ($phone ?: 'WhatsApp contact'),
            'phone' => $phone,
            'label' => $name && $phone ? $name."\n".$phone : ($name ?: $phone ?: 'WhatsApp contact'),
        ];
    }

    public function fromLead(?CrmLead $lead, ?string $fallbackPhone = null): CustomerIdentity
    {
        if (! $lead instanceof CrmLead) {
            return CustomerIdentity::fromArray([
                'phone_number' => $fallbackPhone,
            ]);
        }

        $meta = is_array($lead->identity) ? $lead->identity : [];
        $phone = $lead->whatsapp ?: $lead->phone ?: $fallbackPhone;
        $name = trim((string) $lead->name);
        $source = is_string($meta['name_source'] ?? null) ? $meta['name_source'] : null;
        if ($source === null && $name !== '' && ! CustomerNameExtractor::isPlaceholder($name)) {
            $source = CustomerIdentity::SOURCE_PROFILE;
        }

        $confirmed = [];
        $unknown = [];
        $fields = [
            'customer_name' => $name !== '' && ! CustomerNameExtractor::isPlaceholder($name) ? $name : null,
            'phone_number' => PhoneIdentity::display($phone),
            'business_name' => $lead->atelier_name,
            'business_type' => $meta['business_type'] ?? null,
            'email' => $lead->email,
            'city' => $lead->governorate,
            'branches' => $lead->branches_count,
        ];
        foreach ($fields as $key => $value) {
            if ($value !== null && $value !== '' && $value !== 0) {
                $confirmed[$key] = $value;
            } else {
                $unknown[] = $key;
            }
        }

        return new CustomerIdentity(
            customerId: $lead->id,
            phoneNumber: PhoneIdentity::display($phone),
            whatsappPushName: $this->names->extractPushName(is_string($meta['whatsapp_push_name'] ?? null) ? $meta['whatsapp_push_name'] : null),
            customerName: $fields['customer_name'],
            businessName: $lead->atelier_name ? trim((string) $lead->atelier_name) : null,
            businessType: is_string($meta['business_type'] ?? null) ? $meta['business_type'] : null,
            email: $lead->email ? trim((string) $lead->email) : null,
            city: $lead->governorate ? trim((string) $lead->governorate) : null,
            nameSource: $source,
            nameConfidence: is_string($meta['name_confidence'] ?? null) ? $meta['name_confidence'] : ($source ? CustomerIdentity::CONFIDENCE_HIGH : null),
            demoTenantId: $lead->tenant_id ? (int) $lead->tenant_id : null,
            askedForName: (bool) ($meta['asked_for_name'] ?? false),
            confirmed: $confirmed,
            unknown: $unknown,
        );
    }
}
