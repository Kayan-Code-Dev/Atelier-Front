<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Identity;

use App\Models\Central\CrmLead;
use App\Models\Central\Tenant;
use App\Services\Platform\DemoTenantService;
use App\Services\Tenant\TenantUserDirectoryService;

/**
 * Maps CrmLead ↔ demo tenant without creating a parallel identity store.
 */
final class AiSalesDemoBindingService
{
    public function __construct(
        private readonly DemoAccountIdentityService $naming,
        private readonly CustomerIdentityPresenter $presenter,
        private readonly TenantUserDirectoryService $directory,
        private readonly DemoTenantService $demos,
    ) {}

    /**
     * @return array{
     *   reused: bool,
     *   created: bool,
     *   usable: bool,
     *   tenant_id:?int,
     *   tenant_name:?string,
     *   admin_name:?string,
     *   email:?string,
     *   reason:?string
     * }
     */
    public function proposeOrReuse(CrmLead $lead, bool $provision = false): array
    {
        $existing = $this->existingTenant($lead);
        $identity = $this->presenter->fromLead($lead);
        if ($existing instanceof Tenant) {
            if ($lead->tenant_id === null) {
                $lead->tenant_id = $existing->id;
                $lead->save();
            }

            return [
                'reused' => true,
                'created' => false,
                'usable' => true,
                'tenant_id' => (int) $existing->id,
                'tenant_name' => $existing->name,
                'admin_name' => is_array($existing->metadata) ? ($existing->metadata['admin_name'] ?? $existing->name) : $existing->name,
                'email' => is_array($existing->metadata) ? ($existing->metadata['admin_email'] ?? null) : null,
                'reason' => 'existing_demo',
            ];
        }

        $proposal = $this->naming->propose($identity, fn (string $email): bool => $this->directory->findTenantByEmail($email) !== null);
        if (! $proposal['usable']) {
            return [
                'reused' => false,
                'created' => false,
                'usable' => false,
                'tenant_id' => null,
                'tenant_name' => $proposal['tenant_name'],
                'admin_name' => $proposal['admin_name'],
                'email' => $proposal['email'],
                'reason' => 'missing_professional_identity',
            ];
        }

        if (! $provision) {
            return [
                'reused' => false,
                'created' => false,
                'usable' => true,
                'tenant_id' => null,
                'tenant_name' => $proposal['tenant_name'],
                'admin_name' => $proposal['admin_name'],
                'email' => $proposal['email'],
                'reason' => 'proposed',
            ];
        }

        $password = bin2hex(random_bytes(8));
        $result = $this->demos->createAndProvision([
            'name' => $proposal['tenant_name'],
            'email' => $proposal['email'],
            'password' => $password,
            'phone' => $identity->phoneNumber,
            'days' => 7,
            'signup_channel' => 'ai_sales',
            'lead_id' => $lead->id,
            'admin_name' => $proposal['admin_name'],
        ]);
        $tenant = $result['tenant'];
        $lead->tenant_id = $tenant->id;
        $lead->email = $lead->email ?: $proposal['email'];
        $lead->save();

        return [
            'reused' => false,
            'created' => true,
            'usable' => true,
            'tenant_id' => (int) $tenant->id,
            'tenant_name' => $proposal['tenant_name'],
            'admin_name' => $proposal['admin_name'],
            'email' => $proposal['email'],
            'reason' => 'created',
        ];
    }

    public function existingTenant(CrmLead $lead): ?Tenant
    {
        if ($lead->tenant_id) {
            $tenant = Tenant::query()->find($lead->tenant_id);
            if ($tenant instanceof Tenant) {
                return $tenant;
            }
        }

        $phone = PhoneIdentity::digits((string) ($lead->whatsapp ?: $lead->phone));
        if ($phone === '') {
            return null;
        }

        $tenants = Tenant::query()
            ->where('metadata->source', 'demo')
            ->latest('id')
            ->limit(50)
            ->get();
        foreach ($tenants as $tenant) {
            $meta = is_array($tenant->metadata) ? $tenant->metadata : [];
            if ((int) ($meta['crm_lead_id'] ?? 0) === (int) $lead->id) {
                return $tenant;
            }
            if (PhoneIdentity::matches($phone, is_string($meta['phone'] ?? null) ? $meta['phone'] : null)) {
                return $tenant;
            }
        }

        return null;
    }
}
