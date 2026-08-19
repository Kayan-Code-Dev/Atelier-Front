<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use App\Models\Tenant\HrDepartment;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\User;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Domain\WhatsAppSessionKey;
use DressnMore\SmartAssistantProduct\Infrastructure\WhatsAppWeb\WhatsAppGatewayClient;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantChannelConnection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Multi-number WhatsApp (QR/Baileys): each row is one number with an assistant
 * name, department, and owning employee.
 */
final class WhatsAppNumberService
{
    public function __construct(
        private readonly WhatsAppGatewayClient $gateway,
    ) {}

    public function canManageAll(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->roles()
            ->whereHas('permissions', function ($query): void {
                $query->whereIn('key', ['smart_assistant.settings', 'users.manage', 'roles.manage']);
            })
            ->exists();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listFor(Tenant $tenant, User $user): array
    {
        $rows = SmartAssistantChannelConnection::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('channel_type', SocialChannelCatalog::WHATSAPP)
            ->orderByRaw('user_id is null desc')
            ->orderBy('id')
            ->get();

        $manageAll = $this->canManageAll($user);
        $out = [];
        foreach ($rows as $row) {
            if (! $manageAll && (int) ($row->user_id ?? 0) !== (int) $user->id) {
                continue;
            }
            $out[] = $this->snapshot($row);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createFor(Tenant $tenant, User $user, array $data): array
    {
        $ownerId = $this->resolveOwnerUserId($tenant, $user, $data);
        $row = new SmartAssistantChannelConnection();
        $row->tenant_id = (int) $tenant->id;
        $row->channel_type = SocialChannelCatalog::WHATSAPP;
        $row->user_id = $ownerId;
        $row->status = 'disconnected';
        $row->auto_reply_enabled = true;
        $row->auto_reply_mode = (string) config('smart-assistant-product.whatsapp.auto_reply_mode', 'sales');
        $this->applyIdentity($row, $user, $data);
        $row->save();
        $row->session_key = WhatsAppSessionKey::forConnection((int) $tenant->id, (int) $row->id);
        $row->save();

        return $this->snapshot($row->fresh());
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateFor(Tenant $tenant, User $user, int $id, array $data): array
    {
        $row = $this->requireRow($tenant, $user, $id);
        $this->applyIdentity($row, $user, $data);
        if (array_key_exists('auto_reply_enabled', $data)) {
            $row->auto_reply_enabled = (bool) $data['auto_reply_enabled'];
        }
        if (array_key_exists('auto_reply_mode', $data) && is_string($data['auto_reply_mode'])) {
            $row->auto_reply_mode = $data['auto_reply_mode'];
        }
        $row->save();

        return $this->snapshot($row);
    }

    /**
     * @return array<string, mixed>
     */
    public function startSession(Tenant $tenant, User $user, int $id): array
    {
        $row = $this->requireRow($tenant, $user, $id);
        $this->ensureSessionKey($row);
        $state = $this->gateway->createSession($row->session_key);
        $row->status = 'disconnected';
        $row->save();

        return [
            'number' => $this->snapshot($row),
            'session' => $state['session'] ?? $state,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function qr(Tenant $tenant, User $user, int $id): array
    {
        $row = $this->requireRow($tenant, $user, $id);
        $this->ensureSessionKey($row);

        return $this->gateway->qr($row->session_key);
    }

    /**
     * @return array<string, mixed>
     */
    public function status(Tenant $tenant, User $user, int $id): array
    {
        $row = $this->requireRow($tenant, $user, $id);
        $this->ensureSessionKey($row);

        return $this->gateway->status($row->session_key);
    }

    /**
     * @return array<string, mixed>
     */
    public function disconnect(Tenant $tenant, User $user, int $id): array
    {
        $row = $this->requireRow($tenant, $user, $id);
        $this->ensureSessionKey($row);
        try {
            $this->gateway->disconnect($row->session_key);
        } catch (RuntimeException) {
            // already gone
        }
        $row->status = 'disconnected';
        $row->external_account_id = null;
        $row->save();

        return $this->snapshot($row);
    }

    /**
     * Compatibility: current user's number, or a new one, or the atelier-wide row for owners.
     */
    public function getOrCreateMine(Tenant $tenant, User $user): SmartAssistantChannelConnection
    {
        $mine = SmartAssistantChannelConnection::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('channel_type', SocialChannelCatalog::WHATSAPP)
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->first();
        if ($mine instanceof SmartAssistantChannelConnection) {
            $this->ensureSessionKey($mine);

            return $mine;
        }

        if ($this->canManageAll($user)) {
            $legacy = SmartAssistantChannelConnection::query()
                ->where('tenant_id', (int) $tenant->id)
                ->where('channel_type', SocialChannelCatalog::WHATSAPP)
                ->whereNull('user_id')
                ->orderBy('id')
                ->first();
            if ($legacy instanceof SmartAssistantChannelConnection) {
                $this->ensureSessionKey($legacy);

                return $legacy;
            }
        }

        $created = $this->createFor($tenant, $user, []);

        return SmartAssistantChannelConnection::query()->findOrFail((int) $created['id']);
    }

    public function findBySessionKey(string $raw): ?SmartAssistantChannelConnection
    {
        $parsed = WhatsAppSessionKey::parse($raw);
        $tenantId = $parsed['tenant_id'];
        if ($tenantId <= 0) {
            return null;
        }

        if ($parsed['connection_id'] !== null) {
            $byId = SmartAssistantChannelConnection::query()
                ->where('id', $parsed['connection_id'])
                ->where('tenant_id', $tenantId)
                ->where('channel_type', SocialChannelCatalog::WHATSAPP)
                ->first();
            if ($byId instanceof SmartAssistantChannelConnection) {
                return $byId;
            }
        }

        $byKey = SmartAssistantChannelConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('channel_type', SocialChannelCatalog::WHATSAPP)
            ->where('session_key', $parsed['session_key'])
            ->first();
        if ($byKey instanceof SmartAssistantChannelConnection) {
            return $byKey;
        }

        return SmartAssistantChannelConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('channel_type', SocialChannelCatalog::WHATSAPP)
            ->where(function ($q) use ($tenantId): void {
                $q->where('session_key', (string) $tenantId)->orWhereNull('session_key');
            })
            ->orderBy('id')
            ->first();
    }

    public function resolveConnectedSessionKey(int $tenantId, ?int $preferredUserId = null): ?string
    {
        $query = SmartAssistantChannelConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('channel_type', SocialChannelCatalog::WHATSAPP)
            ->where('status', 'connected');

        $preferred = null;
        if ($preferredUserId !== null && $preferredUserId > 0) {
            $preferred = (clone $query)->where('user_id', $preferredUserId)->orderBy('id')->first();
        }
        $row = $preferred instanceof SmartAssistantChannelConnection
            ? $preferred
            : $query->orderByRaw('user_id is null desc')->orderBy('id')->first();

        if (! $row instanceof SmartAssistantChannelConnection) {
            return null;
        }
        $this->ensureSessionKey($row);

        return $row->session_key;
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function departments(): array
    {
        try {
            if (! Schema::connection('tenant')->hasTable('hr_departments')) {
                return [];
            }
        } catch (Throwable) {
            return [];
        }

        return HrDepartment::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (HrDepartment $d): array => [
                'id' => (int) $d->id,
                'name' => (string) $d->name,
            ])
            ->all();
    }

    public function requireRow(Tenant $tenant, User $user, int $id): SmartAssistantChannelConnection
    {
        $row = SmartAssistantChannelConnection::query()
            ->where('id', $id)
            ->where('tenant_id', (int) $tenant->id)
            ->where('channel_type', SocialChannelCatalog::WHATSAPP)
            ->first();
        if (! $row instanceof SmartAssistantChannelConnection) {
            throw ValidationException::withMessages(['id' => ['رقم الواتساب غير موجود.']]);
        }
        if (! $this->canManageAll($user) && (int) ($row->user_id ?? 0) !== (int) $user->id) {
            throw ValidationException::withMessages(['id' => ['لا يمكنك إدارة رقم واتساب لموظف آخر.']]);
        }
        $this->ensureSessionKey($row);

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(?SmartAssistantChannelConnection $row): array
    {
        if ($row === null) {
            return [
                'id' => null,
                'status' => 'disconnected',
                'assistant_name' => null,
                'department_id' => null,
                'department_name' => null,
                'phone_number' => null,
                'display_name' => null,
                'auto_reply_enabled' => false,
                'mine' => false,
            ];
        }

        $meta = is_array($row->meta) ? $row->meta : [];
        $phone = $meta['display_phone_number'] ?? $meta['phone_number'] ?? null;
        $gwStatus = $row->status;
        $gwPhone = $phone;
        $gwName = $row->display_name;
        try {
            $this->ensureSessionKey($row);
            $state = $this->gateway->status($row->session_key);
            $session = is_array($state['session'] ?? null) ? $state['session'] : $state;
            $raw = (string) ($session['status'] ?? $row->status);
            $gwStatus = match ($raw) {
                'open', 'connected' => 'connected',
                'qr_required', 'connecting', 'reconnecting' => 'connecting',
                default => (string) $row->status,
            };
            if (! empty($session['phone'])) {
                $gwPhone = (string) $session['phone'];
            }
            if (! empty($session['display_name'])) {
                $gwName = (string) $session['display_name'];
            }
            if ($gwStatus === 'connected' && $gwPhone) {
                $meta['display_phone_number'] = $gwPhone;
                $row->meta = $meta;
                $row->status = 'connected';
                if (is_string($gwName) && $gwName !== '') {
                    $row->display_name = $gwName;
                }
                $row->save();
            }
        } catch (Throwable) {
            // gateway unreachable — return stored row
        }

        return [
            'id' => (int) $row->id,
            'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
            'assistant_name' => $row->assistant_name,
            'department_id' => $row->department_id !== null ? (int) $row->department_id : null,
            'department_name' => $row->department_name,
            'session_key' => $row->session_key,
            'status' => $gwStatus,
            'phone_number' => $gwPhone,
            'display_name' => $gwName,
            'auto_reply_enabled' => (bool) $row->auto_reply_enabled,
            'auto_reply_mode' => (string) $row->auto_reply_mode,
            'connected_at' => $row->connected_at?->toIso8601String(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyIdentity(SmartAssistantChannelConnection $row, User $actor, array $data): void
    {
        if (array_key_exists('assistant_name', $data)) {
            $name = trim((string) ($data['assistant_name'] ?? ''));
            $row->assistant_name = $name !== '' ? $name : null;
        } elseif ($row->assistant_name === null || $row->assistant_name === '') {
            $row->assistant_name = $actor->name ?: null;
        }

        $departmentId = array_key_exists('department_id', $data)
            ? ($data['department_id'] !== null && $data['department_id'] !== '' ? (int) $data['department_id'] : null)
            : $row->department_id;
        $departmentName = array_key_exists('department_name', $data)
            ? trim((string) ($data['department_name'] ?? ''))
            : (string) ($row->department_name ?? '');

        if ($departmentId === null && ($row->exists === false || $row->department_id === null)) {
            $employee = HrEmployee::query()->where('user_id', $row->user_id ?? $actor->id)->first();
            if ($employee instanceof HrEmployee) {
                $departmentId = $employee->department_id !== null ? (int) $employee->department_id : null;
                if ($departmentName === '' && $employee->department_id) {
                    $departmentName = (string) (HrDepartment::query()->find($employee->department_id)?->name ?? '');
                }
            }
        }

        if ($departmentId !== null) {
            $dept = HrDepartment::query()->find($departmentId);
            $row->department_id = $departmentId;
            $row->department_name = $dept?->name ?: ($departmentName !== '' ? $departmentName : null);
        } elseif (array_key_exists('department_id', $data) || array_key_exists('department_name', $data)) {
            $row->department_id = null;
            $row->department_name = $departmentName !== '' ? $departmentName : null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveOwnerUserId(Tenant $tenant, User $actor, array $data): int
    {
        $requested = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        if ($requested > 0 && $this->canManageAll($actor)) {
            return $requested;
        }

        return (int) $actor->id;
    }

    private function ensureSessionKey(SmartAssistantChannelConnection $row): void
    {
        if (filled($row->session_key)) {
            return;
        }
        $row->session_key = $row->user_id === null
            ? WhatsAppSessionKey::legacy((int) $row->tenant_id)
            : WhatsAppSessionKey::forConnection((int) $row->tenant_id, (int) $row->id);
        $row->save();
    }
}
