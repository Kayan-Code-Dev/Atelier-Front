<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use DressnMore\SmartAssistantProduct\Application\AiQuotaService;
use DressnMore\SmartAssistantProduct\Application\WhatsAppSalesAgentService;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantAgentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant-facing AI assistant settings: identity, business texts, FAQ and
 * capability toggles. Stored per-tenant in the central DB.
 */
final class AgentSettingsController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ChannelConnectionService $channels,
        private readonly WhatsAppSalesAgentService $salesAgent,
        private readonly AiQuotaService $quotaService,
    ) {}

    public function show(): JsonResponse
    {
        $tenantId = (int) $this->tenantContext->requireTenant()->id;
        $settings = SmartAssistantAgentSettings::forTenant($tenantId);

        $channels = $this->channels->listChannels((string) $tenantId);
        $wa = collect($channels)->firstWhere('type', SocialChannelCatalog::WHATSAPP);

        return ApiResponse::success([
            'assistant_name' => $settings->assistant_name,
            'display_name' => $settings->display_name,
            'role' => $settings->role,
            'tone' => $settings->tone,
            'style' => $settings->style,
            'language' => $settings->language,
            'status' => $settings->status,
            'avatar' => $settings->avatar,
            'preview_greeting' => $settings->previewGreeting(),
            'options' => [
                'roles' => SmartAssistantAgentSettings::ROLES,
                'tones' => SmartAssistantAgentSettings::TONES,
                'styles' => SmartAssistantAgentSettings::STYLES,
                'languages' => SmartAssistantAgentSettings::LANGUAGES,
            ],
            'personality' => $settings->personality,
            'business_instructions' => $settings->business_instructions,
            'welcome_message' => $settings->welcome_message,
            'handoff_message' => $settings->handoff_message,
            'faq' => $settings->faq ?? [],
            'business_hours' => [
                'from' => $settings->business_hours_from,
                'to' => $settings->business_hours_to,
                'after_hours_behavior' => $settings->after_hours_behavior ?: 'reply',
                'away_message' => $settings->away_message,
            ],
            'capabilities' => [
                'auto_reply_enabled' => $settings->auto_reply_enabled,
                'can_register_customers' => $settings->can_register_customers,
                'can_create_invoices' => $settings->can_create_invoices,
                'can_show_prices' => $settings->can_show_prices,
            ],
            'channel' => [
                'type' => 'whatsapp',
                'status' => $wa['status'] ?? 'disconnected',
                'auto_reply_enabled' => (bool) ($wa['auto_reply_enabled'] ?? false),
                'auto_reply_mode' => (string) ($wa['auto_reply_mode'] ?? 'template'),
                'phone_number' => $wa['phone_number'] ?? null,
                'display_name' => $wa['display_name'] ?? null,
            ],
            'quota' => $this->quotaService->snapshot($this->tenantContext->requireTenant()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'assistant_name' => ['nullable', 'string', 'max:120'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'in:general,sales,support,reservations,customer_success'],
            'tone' => ['nullable', 'string', 'in:friendly,professional,luxury,warm,concise'],
            'style' => ['nullable', 'string', 'in:short_direct,conversational,consultative,detailed'],
            'language' => ['nullable', 'string', 'in:ar,en,ar_en'],
            'status' => ['nullable', 'string', 'in:active,disabled'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'personality' => ['nullable', 'string', 'max:3000'],
            'business_instructions' => ['nullable', 'string', 'max:6000'],
            'welcome_message' => ['nullable', 'string', 'max:1500'],
            'handoff_message' => ['nullable', 'string', 'max:1500'],
            'faq' => ['nullable', 'array', 'max:30'],
            'faq.*.q' => ['required_with:faq', 'string', 'max:500'],
            'faq.*.a' => ['required_with:faq', 'string', 'max:1500'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
            'can_register_customers' => ['nullable', 'boolean'],
            'can_create_invoices' => ['nullable', 'boolean'],
            'can_show_prices' => ['nullable', 'boolean'],
            'auto_reply_mode' => ['nullable', 'string', 'in:template,planner,sales,off'],
            'business_hours_from' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'business_hours_to' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'after_hours_behavior' => ['nullable', 'string', 'in:reply,away_message,off'],
            'away_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenantId = (int) $this->tenantContext->requireTenant()->id;
        $settings = SmartAssistantAgentSettings::forTenant($tenantId);

        foreach (['assistant_name', 'display_name', 'role', 'tone', 'style', 'language', 'status', 'avatar', 'personality', 'business_instructions', 'welcome_message', 'handoff_message'] as $field) {
            if (array_key_exists($field, $data)) {
                $settings->{$field} = $data[$field];
            }
        }
        if (array_key_exists('faq', $data)) {
            $settings->faq = is_array($data['faq'])
                ? array_values(array_map(static fn ($f): array => [
                    'q' => (string) ($f['q'] ?? ''),
                    'a' => (string) ($f['a'] ?? ''),
                ], $data['faq']))
                : [];
        }
        foreach (['business_hours_from', 'business_hours_to', 'after_hours_behavior', 'away_message'] as $hoursField) {
            if (array_key_exists($hoursField, $data)) {
                $settings->{$hoursField} = $data[$hoursField];
            }
        }
        foreach (['auto_reply_enabled', 'can_register_customers', 'can_create_invoices', 'can_show_prices'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $settings->{$flag} = (bool) $data[$flag];
            }
        }
        $settings->save();

        // Reflect the master switch + mode onto the WhatsApp channel connection.
        $payload = [];
        if (array_key_exists('auto_reply_enabled', $data)) {
            $payload['auto_reply_enabled'] = (bool) $data['auto_reply_enabled'];
        }
        if (array_key_exists('auto_reply_mode', $data)) {
            $payload['auto_reply_mode'] = (string) $data['auto_reply_mode'];
        }
        if ($payload !== []) {
            $this->channels->updateChannelSettings((string) $tenantId, SocialChannelCatalog::WHATSAPP, $payload);
        }

        return $this->show();
    }

    /**
     * "Test your Assistant" — simulation chat. Same pipeline as production
     * but never consumes quota and never executes destructive actions.
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $tenant = $this->tenantContext->requireTenant();
        $reply = $this->salesAgent->previewReply($tenant, trim((string) $data['message']));

        return ApiResponse::success([
            'reply' => $reply,
            'simulation' => true,
        ]);
    }

    public function resetPreview(): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->salesAgent->resetPreview($tenant);

        return ApiResponse::success(['reset' => true], 'تمت إعادة ضبط محادثة المعاينة');
    }
}
