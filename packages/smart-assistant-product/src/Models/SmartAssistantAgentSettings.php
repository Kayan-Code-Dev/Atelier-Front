<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-tenant AI assistant entity: identity, personality, texts, permissions.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $assistant_name
 * @property string|null $display_name
 * @property string $role
 * @property string $tone
 * @property string $style
 * @property string $language
 * @property string $status
 * @property string|null $avatar
 * @property string|null $personality
 * @property string|null $business_instructions
 * @property string|null $welcome_message
 * @property string|null $handoff_message
 * @property array<int, array{q: string, a: string}>|null $faq
 * @property bool $auto_reply_enabled
 * @property bool $can_register_customers
 * @property bool $can_create_invoices
 * @property bool $can_show_prices
 */
final class SmartAssistantAgentSettings extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    public const ROLES = ['general', 'sales', 'support', 'reservations', 'customer_success'];
    public const TONES = ['friendly', 'professional', 'luxury', 'warm', 'concise'];
    public const STYLES = ['short_direct', 'conversational', 'consultative', 'detailed'];
    public const LANGUAGES = ['ar', 'en', 'ar_en'];
    public const AFTER_HOURS_BEHAVIORS = ['reply', 'away_message', 'off'];

    protected $connection = 'central';

    protected $table = 'smart_assistant_agent_settings';

    protected $fillable = [
        'tenant_id',
        'assistant_name',
        'display_name',
        'role',
        'tone',
        'style',
        'language',
        'status',
        'avatar',
        'personality',
        'business_instructions',
        'welcome_message',
        'handoff_message',
        'faq',
        'auto_reply_enabled',
        'can_register_customers',
        'can_create_invoices',
        'can_show_prices',
        'business_hours_from',
        'business_hours_to',
        'after_hours_behavior',
        'away_message',
    ];

    protected function casts(): array
    {
        return [
            'faq' => 'array',
            'auto_reply_enabled' => 'boolean',
            'can_register_customers' => 'boolean',
            'can_create_invoices' => 'boolean',
            'can_show_prices' => 'boolean',
        ];
    }

    public static function forTenant(int $tenantId): self
    {
        /** @var self $settings */
        $settings = static::query()->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['assistant_name' => 'سارة'],
        );

        return $settings;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Business-hours gate: null window = always open. Behavior decided by
     * after_hours_behavior ('reply' | 'away_message' | 'off').
     */
    public function isWithinBusinessHours(?\DateTimeInterface $now = null): bool
    {
        $from = $this->business_hours_from;
        $to = $this->business_hours_to;
        if ($from === null || $from === '' || $to === null || $to === '') {
            return true;
        }
        if (! preg_match('/^([01]?\\d|2[0-3]):[0-5]\\d$/', $from) || ! preg_match('/^([01]?\\d|2[0-3]):[0-5]\\d$/', $to)) {
            return true;
        }

        $hm = ($now ?? now())->format('H:i');
        if ($from <= $to) {
            return $hm >= $from && $hm <= $to;
        }

        // Overnight window (e.g. 18:00 → 02:00).
        return $hm >= $from || $hm <= $to;
    }

    /**
     * Deterministic greeting preview generated from the current
     * identity/personality settings (no AI call, no quota consumption).
     */
    public function previewGreeting(): string
    {
        $name = $this->display_name ?: $this->assistant_name;

        $greeting = match ($this->tone) {
            'professional' => "مرحبًا بك. معك {$name}. كيف يمكنني خدمتك اليوم؟",
            'luxury' => "أهلاً وسهلاً بك ✨ معك {$name}. يسعدني أن أرافقك في اختيار ما يليق بك اليوم.",
            'warm' => "حبيبتي/حبيبي، أهلًا فيك 💛 أنا {$name}. قوليلي كيف أقدر أساعدك؟",
            'concise' => "مرحبًا، {$name}. تفضل، كيف أساعدك؟",
            default => "مرحبًا 👋 أنا {$name}! كيف أقدر أساعدك اليوم؟",
        };

        if ($this->language === 'en') {
            $greeting = match ($this->tone) {
                'professional' => "Hello, this is {$name}. How may I assist you today?",
                'luxury' => "Welcome ✨ This is {$name}. It would be my pleasure to assist you today.",
                'warm' => "Hi there 💛 I'm {$name}. How can I help you?",
                'concise' => "Hi, {$name}. How can I help?",
                default => "Hi 👋 I'm {$name}! How can I help you today?",
            };
        }

        return $greeting;
    }
}
