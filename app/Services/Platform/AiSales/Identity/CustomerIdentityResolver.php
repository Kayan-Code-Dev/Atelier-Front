<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Identity;

use App\Models\Central\CrmLead;
use App\Models\Central\PlatformAiSalesConversation;
use App\Services\Platform\AiSales\AiSalesQueryService;
use App\Support\AiSales\AiSalesChannel;
use App\Support\AiSales\AiSalesHandoffState;

/**
 * One customer identity: CrmLead. Resolves WhatsApp/phone to an existing lead
 * instead of creating duplicates.
 */
final class CustomerIdentityResolver
{
    public function __construct(
        private readonly CustomerNameExtractor $names = new CustomerNameExtractor(),
        private readonly CustomerIdentityPresenter $presenter = new CustomerIdentityPresenter(),
    ) {}

    /**
     * @param  array<string, mixed>  $inbound
     */
    public function resolve(array $inbound): CrmLead
    {
        $leadId = isset($inbound['lead_id']) ? (int) $inbound['lead_id'] : 0;
        $phone = PhoneIdentity::digits((string) ($inbound['phone'] ?? $inbound['whatsapp'] ?? $inbound['from'] ?? $inbound['external_id'] ?? ''));
        $pushName = $this->names->extractPushName(
            is_string($inbound['push_name'] ?? null) ? $inbound['push_name'] : (is_string($inbound['whatsapp_push_name'] ?? null) ? $inbound['whatsapp_push_name'] : null)
        );
        $channel = AiSalesChannel::fromStored($inbound['channel'] ?? 'whatsapp')->value;

        $lead = null;
        if ($leadId > 0) {
            $lead = CrmLead::query()->find($leadId);
        }
        if ($lead === null && $phone !== '') {
            $lead = $this->findByPhone($phone);
        }
        if ($lead === null && ! empty($inbound['external_id'])) {
            $conversation = PlatformAiSalesConversation::query()
                ->where('channel', $channel)
                ->where('external_id', (string) $inbound['external_id'])
                ->first();
            $lead = $conversation?->lead;
        }

        if ($lead === null) {
            $name = $pushName ?? '';
            $lead = CrmLead::query()->create([
                'name' => $name,
                'phone' => $phone !== '' ? $phone : null,
                'whatsapp' => $phone !== '' ? $phone : null,
                'email' => $inbound['email'] ?? null,
                'atelier_name' => $inbound['business'] ?? $inbound['atelier_name'] ?? null,
                'governorate' => $inbound['city'] ?? $inbound['governorate'] ?? null,
                'activity' => $channel,
                'source' => AiSalesQueryService::SOURCE,
                'status' => 'new',
                'score' => 0,
                'temperature' => 'cold',
                'handoff_status' => AiSalesHandoffState::AiActive->value,
                'identity' => [
                    'name_source' => $pushName !== null ? CustomerIdentity::SOURCE_WHATSAPP_PUSH_NAME : null,
                    'name_confidence' => $pushName !== null ? CustomerIdentity::CONFIDENCE_MEDIUM : null,
                    'whatsapp_push_name' => $pushName,
                    'asked_for_name' => false,
                ],
            ]);
        } else {
            $this->rememberPushName($lead, $pushName, $phone, $channel);
        }

        return $lead->fresh() ?? $lead;
    }

    public function findByPhone(string $phone): ?CrmLead
    {
        $key = PhoneIdentity::matchKey($phone);
        if ($key === '') {
            return null;
        }

        $candidates = CrmLead::query()
            ->where(function ($q) use ($key): void {
                $q->where('phone', 'like', '%'.$key)
                    ->orWhere('whatsapp', 'like', '%'.$key);
            })
            ->orderByRaw("CASE WHEN source = ? THEN 0 ELSE 1 END", [AiSalesQueryService::SOURCE])
            ->orderBy('id')
            ->limit(20)
            ->get();

        foreach ($candidates as $candidate) {
            if (PhoneIdentity::matches($key, (string) ($candidate->whatsapp ?: $candidate->phone))) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Apply extracted or inbound identity onto the canonical lead without creating duplicates.
     *
     * @param  array<string, mixed>  $extracted
     */
    public function applyToLead(CrmLead $lead, array $extracted, ?string $pushName = null): CrmLead
    {
        $meta = is_array($lead->identity) ? $lead->identity : [];
        $currentSource = is_string($meta['name_source'] ?? null) ? $meta['name_source'] : null;
        if ($currentSource === null && trim((string) $lead->name) !== '' && ! CustomerNameExtractor::isPlaceholder((string) $lead->name)) {
            $currentSource = CustomerIdentity::SOURCE_PROFILE;
        }

        $incomingName = is_string($extracted['customer_name'] ?? null) ? trim($extracted['customer_name']) : null;
        $incomingSource = is_string($extracted['name_source'] ?? null) ? $extracted['name_source'] : null;
        $incomingConfidence = is_string($extracted['name_confidence'] ?? null) ? $extracted['name_confidence'] : null;
        $safePush = $this->names->extractPushName($pushName);

        if ($incomingName && $this->mayReplaceName($currentSource, $incomingSource ?: CustomerIdentity::SOURCE_INFERRED, (string) $lead->name, $incomingName)) {
            $lead->name = $incomingName;
            $meta['name_source'] = $incomingSource ?: ($currentSource ?: CustomerIdentity::SOURCE_INFERRED);
            $meta['name_confidence'] = $incomingConfidence ?: CustomerIdentity::CONFIDENCE_HIGH;
        } elseif ((trim((string) $lead->name) === '' || CustomerNameExtractor::isPlaceholder((string) $lead->name)) && $safePush !== null) {
            $lead->name = $safePush;
            $meta['name_source'] = CustomerIdentity::SOURCE_WHATSAPP_PUSH_NAME;
            $meta['name_confidence'] = CustomerIdentity::CONFIDENCE_MEDIUM;
        }

        $business = is_string($extracted['business_name'] ?? null) ? trim($extracted['business_name']) : '';
        if ($business !== '' && (trim((string) $lead->atelier_name) === '' || $incomingSource === CustomerIdentity::SOURCE_EXPLICIT_USER)) {
            $lead->atelier_name = $business;
        }

        if ($safePush !== null) {
            $meta['whatsapp_push_name'] = $safePush;
        }
        if (! empty($extracted['business_type'])) {
            $meta['business_type'] = $extracted['business_type'];
        }
        if (array_key_exists('asked_for_name', $extracted)) {
            $meta['asked_for_name'] = (bool) $extracted['asked_for_name'];
        }

        $lead->identity = $meta;
        $lead->save();

        return $lead->fresh() ?? $lead;
    }

    public function identityFor(CrmLead $lead, ?string $fallbackPhone = null): CustomerIdentity
    {
        return $this->presenter->fromLead($lead, $fallbackPhone);
    }

    public function markNameAsked(CrmLead $lead): CrmLead
    {
        $meta = is_array($lead->identity) ? $lead->identity : [];
        $meta['asked_for_name'] = true;
        $lead->identity = $meta;
        $lead->save();

        return $lead->fresh() ?? $lead;
    }

    private function mayReplaceName(?string $currentSource, string $incomingSource, string $currentName, string $incomingName): bool
    {
        if ($incomingName === '' || CustomerNameExtractor::isPlaceholder($incomingName)) {
            return false;
        }
        if (trim($currentName) === '' || CustomerNameExtractor::isPlaceholder($currentName)) {
            return true;
        }
        if ($incomingName === trim($currentName)) {
            return true;
        }

        return CustomerIdentity::sourceRank($incomingSource) >= CustomerIdentity::sourceRank($currentSource);
    }

    private function rememberPushName(CrmLead $lead, ?string $pushName, string $phone, string $channel): void
    {
        $dirty = false;
        if ($phone !== '') {
            if (trim((string) $lead->phone) === '') {
                $lead->phone = $phone;
                $dirty = true;
            }
            if (trim((string) $lead->whatsapp) === '') {
                $lead->whatsapp = $phone;
                $dirty = true;
            }
        }
        if ($channel !== '' && trim((string) $lead->activity) === '') {
            $lead->activity = $channel;
            $dirty = true;
        }
        $meta = is_array($lead->identity) ? $lead->identity : [];
        if ($pushName !== null) {
            $meta['whatsapp_push_name'] = $pushName;
            if (trim((string) $lead->name) === '' || CustomerNameExtractor::isPlaceholder((string) $lead->name)) {
                $lead->name = $pushName;
                $meta['name_source'] = CustomerIdentity::SOURCE_WHATSAPP_PUSH_NAME;
                $meta['name_confidence'] = CustomerIdentity::CONFIDENCE_MEDIUM;
            }
            $dirty = true;
        }
        if ($dirty) {
            $lead->identity = $meta;
            $lead->save();
        }
    }
}
