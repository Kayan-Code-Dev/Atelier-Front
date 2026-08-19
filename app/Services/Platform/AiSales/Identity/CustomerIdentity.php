<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Identity;

/**
 * Canonical DressnMore sales-customer identity. Backed by CrmLead — not a second CRM.
 */
final class CustomerIdentity
{
    public const SOURCE_EXPLICIT_USER = 'explicit_user';

    public const SOURCE_PROFILE = 'profile';

    public const SOURCE_WHATSAPP_PUSH_NAME = 'whatsapp_push_name';

    public const SOURCE_INFERRED = 'inferred';

    public const CONFIDENCE_HIGH = 'high';

    public const CONFIDENCE_MEDIUM = 'medium';

    public const CONFIDENCE_LOW = 'low';

    /**
     * @param  list<string>  $unknown
     * @param  array<string, mixed>  $confirmed
     */
    public function __construct(
        public readonly ?int $customerId,
        public readonly ?string $phoneNumber,
        public readonly ?string $whatsappPushName,
        public readonly ?string $customerName,
        public readonly ?string $businessName,
        public readonly ?string $businessType,
        public readonly ?string $email,
        public readonly ?string $city,
        public readonly ?string $nameSource,
        public readonly ?string $nameConfidence,
        public readonly ?int $demoTenantId,
        public readonly bool $askedForName,
        public readonly array $confirmed = [],
        public readonly array $unknown = [],
    ) {}

    public function trustedCustomerName(): ?string
    {
        if (! $this->isUsableName($this->customerName)) {
            return null;
        }
        if ($this->nameConfidence === self::CONFIDENCE_LOW) {
            return null;
        }
        if ($this->nameSource === self::SOURCE_INFERRED && $this->nameConfidence !== self::CONFIDENCE_HIGH) {
            return null;
        }

        return $this->customerName;
    }

    public function greetingName(): ?string
    {
        $name = $this->trustedCustomerName();
        if ($name === null) {
            return null;
        }
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $first = trim((string) ($parts[0] ?? ''));

        return $first !== '' ? $first : $name;
    }

    public function hasTrustedName(): bool
    {
        return $this->trustedCustomerName() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'phone_number' => $this->phoneNumber,
            'whatsapp_push_name' => $this->whatsappPushName,
            'customer_name' => $this->customerName,
            'business_name' => $this->businessName,
            'business_type' => $this->businessType,
            'email' => $this->email,
            'city' => $this->city,
            'name_source' => $this->nameSource,
            'name_confidence' => $this->nameConfidence,
            'demo_tenant_id' => $this->demoTenantId,
            'asked_for_name' => $this->askedForName,
            'confirmed' => $this->confirmed,
            'unknown' => $this->unknown,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            customerId: isset($row['customer_id']) ? (int) $row['customer_id'] : (isset($row['id']) ? (int) $row['id'] : null),
            phoneNumber: self::nullableString($row['phone_number'] ?? $row['phone'] ?? null),
            whatsappPushName: self::nullableString($row['whatsapp_push_name'] ?? null),
            customerName: self::nullableString($row['customer_name'] ?? $row['name'] ?? null),
            businessName: self::nullableString($row['business_name'] ?? $row['business'] ?? $row['atelier_name'] ?? null),
            businessType: self::nullableString($row['business_type'] ?? null),
            email: self::nullableString($row['email'] ?? null),
            city: self::nullableString($row['city'] ?? $row['governorate'] ?? $row['country'] ?? null),
            nameSource: self::nullableString($row['name_source'] ?? null),
            nameConfidence: self::nullableString($row['name_confidence'] ?? null),
            demoTenantId: isset($row['demo_tenant_id']) ? (int) $row['demo_tenant_id'] : (isset($row['tenant_id']) ? (int) $row['tenant_id'] : null),
            askedForName: (bool) ($row['asked_for_name'] ?? false),
            confirmed: is_array($row['confirmed'] ?? null) ? $row['confirmed'] : [],
            unknown: is_array($row['unknown'] ?? null) ? $row['unknown'] : [],
        );
    }

    public static function sourceRank(?string $source): int
    {
        return match ($source) {
            self::SOURCE_EXPLICIT_USER => 40,
            self::SOURCE_PROFILE => 30,
            self::SOURCE_WHATSAPP_PUSH_NAME => 20,
            self::SOURCE_INFERRED => 10,
            default => 0,
        };
    }

    private function isUsableName(?string $name): bool
    {
        $name = trim((string) $name);

        return $name !== '' && ! CustomerNameExtractor::isPlaceholder($name);
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $value === null ? null : (trim((string) $value) !== '' ? trim((string) $value) : null);
        }
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
