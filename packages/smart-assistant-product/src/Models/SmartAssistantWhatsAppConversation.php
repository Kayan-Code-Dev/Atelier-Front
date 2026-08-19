<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $phone
 * @property int|null $customer_id
 * @property array<int, array<string, string>>|null $history
 * @property array<string, mixed>|null $pending_action
 * @property array<string, mixed>|null $state
 * @property string $handler
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 */
final class SmartAssistantWhatsAppConversation extends Model
{
    protected $connection = 'central';

    protected $table = 'smart_assistant_whatsapp_conversations';

    protected $fillable = [
        'tenant_id',
        'phone',
        'customer_id',
        'history',
        'pending_action',
        'state',
        'handler',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'history' => 'array',
            'pending_action' => 'array',
            'state' => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function historyMessages(): array
    {
        $history = is_array($this->history) ? $this->history : [];

        return array_values(array_filter($history, static fn ($m): bool => is_array($m)
            && isset($m['role'], $m['content'])
            && in_array($m['role'], ['user', 'assistant'], true)));
    }

    public function pushHistory(string $role, string $content, int $max = 12): void
    {
        $history = $this->historyMessages();
        $history[] = ['role' => $role, 'content' => mb_substr($content, 0, 2000)];
        if (count($history) > $max) {
            $history = array_slice($history, -$max);
        }
        $this->history = $history;
        $this->last_activity_at = now();
    }

    /**
     * @return array<string, mixed>
     */
    public function stateData(): array
    {
        $state = is_array($this->state) ? $this->state : [];
        $state['known_facts'] = is_array($state['known_facts'] ?? null) ? $state['known_facts'] : [];

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function putState(array $state): void
    {
        $this->state = $state;
        $this->last_activity_at = now();
    }

    public function resetLiveSession(): void
    {
        $this->history = [];
        $this->pending_action = null;
        $this->handler = 'ai';
        $this->putState([
            'known_facts' => [],
            'session_reset_at' => now()->toIso8601String(),
            'greeted' => false,
        ]);
    }
}
