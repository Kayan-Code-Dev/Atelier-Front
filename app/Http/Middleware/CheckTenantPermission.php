<?php

namespace App\Http\Middleware;

use App\Models\Tenant\User;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantPermission
{
    /**
     * @var array<string, list<string>>
     */
    private const LEGACY_ALIASES = [
        'accounting.journal_entries.view' => ['accounting.view', 'accounting.journals.view'],
        'accounting.journal_entries.export' => ['accounting.view', 'accounting.reports.export'],
        'accounting.journals.view' => ['accounting.journal_entries.view', 'accounting.view'],
        'accounting.journals.create' => ['accounting.journal_entries.create'],
        'accounting.journals.update_draft' => ['accounting.journal_entries.update'],
        'accounting.journals.delete_draft' => ['accounting.journal_entries.update'],
        'accounting.journals.post' => ['accounting.journal_entries.approve'],
        'accounting.journals.approve' => ['accounting.journal_entries.approve'],
        'accounting.journals.reverse' => ['accounting.journal_entries.reverse', 'accounting.entries.reverse'],
        'accounting.entries.view' => ['accounting.journal_entries.view', 'accounting.journals.view', 'accounting.view'],
        'accounting.entries.create' => ['accounting.journal_entries.create', 'accounting.journals.create'],
        'accounting.entries.edit' => ['accounting.journal_entries.update', 'accounting.journals.update_draft'],
        'accounting.entries.submit' => ['accounting.journal_entries.create', 'accounting.journals.create'],
        'accounting.entries.approve' => ['accounting.journal_entries.approve', 'accounting.journals.approve', 'accounting.journals.post'],
        'accounting.entries.reverse' => ['accounting.journal_entries.reverse', 'accounting.journals.reverse'],
        'accounting.entries.export' => ['accounting.journal_entries.export', 'accounting.reports.export', 'accounting.view'],
        'accounting.accounts.view' => ['accounting.view'],
        'accounting.reports.view' => ['accounting.view', 'reports.accounting'],
        'accounting.reports.export' => ['accounting.journal_entries.export', 'accounting.view'],
        'accounting.periods.view' => ['accounting.view'],
        'accounting.controls.view' => ['accounting.view'],
        'accounting.assets.view' => ['accounting.view'],
        'accounting.assets.create' => ['accounting.assets.edit'],
        'accounting.assets.edit' => ['accounting.assets.create'],
        'accounting.equity.view' => ['accounting.view'],
        'accounting.liabilities.view' => ['accounting.view'],
        'accounting.reconciliation.view' => ['accounting.view'],
        'accounting.receivables.view' => ['accounting.view'],
        'accounting.payables.view' => ['accounting.view'],
        'dresses.report.view' => ['dresses.view'],
        'notifications.view' => ['settings.view', 'hr.view', 'dashboard.view'],
        'hr.activity.view' => ['hr.dashboard.view', 'hr.view'],
        'website.templates' => ['website.view', 'website.manage'],
        'website.design' => ['website.view', 'website.manage'],
        'website.pages' => ['website.view', 'website.manage'],
        'website.sections' => ['website.view', 'website.manage'],
        'website.content' => ['website.view', 'website.manage'],
        'website.media' => ['website.view', 'website.manage'],
        'website.products' => ['website.view', 'website.manage'],
        'website.services' => ['website.view', 'website.manage'],
        'website.bookings' => ['website.view', 'website.manage'],
        'website.orders' => ['website.view', 'website.manage'],
        'website.leads' => ['website.view', 'website.manage'],
        'website.messages' => ['website.view', 'website.manage'],
        'website.settings' => ['website.view', 'website.manage', 'settings.view'],
        'website.seo' => ['website.view', 'website.manage'],
        'website.analytics' => ['website.view', 'website.manage'],
        'website.marketing' => ['website.view', 'website.manage'],
        'website.domain' => ['website.view', 'website.manage'],
        'website.publish' => ['website.view', 'website.manage'],
        'marketplace.products' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.orders' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.customers' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.sales' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.offers' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.reviews' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.messages' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.bookings' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.website' => ['marketplace.view', 'marketplace.manage'],
        'marketplace.settings' => ['marketplace.view', 'marketplace.manage', 'settings.view'],
        'marketplace.publish' => ['marketplace.view', 'marketplace.manage'],
        'smart_assistant.access' => [
            'smart_assistant.channels',
            'smart_assistant.messages',
            'smart_assistant.comments',
            'smart_assistant.automations',
            'smart_assistant.settings',
        ],
    ];

    public function handle(Request $request, Closure $next, string ...$permissionKeys): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return ApiResponse::unauthorized();
        }

        $keys = $this->normalizeKeys($permissionKeys);
        if ($keys === []) {
            return ApiResponse::forbidden();
        }

        foreach ($keys as $permissionKey) {
            if ($this->userHasPermission($user, $permissionKey)) {
                return $next($request);
            }

            foreach (self::LEGACY_ALIASES[$permissionKey] ?? [] as $alias) {
                if ($this->userHasPermission($user, $alias)) {
                    return $next($request);
                }
            }
        }

        return ApiResponse::forbidden();
    }

    /**
     * @param list<string> $permissionKeys
     * @return list<string>
     */
    private function normalizeKeys(array $permissionKeys): array
    {
        $keys = [];
        foreach ($permissionKeys as $key) {
            foreach (preg_split('/[|,]/', $key) ?: [] as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $keys[] = $part;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    private function userHasPermission(User $user, string $permissionKey): bool
    {
        return $user->roles()
            ->whereHas('permissions', function ($query) use ($permissionKey): void {
                $query->where('key', $permissionKey);
            })
            ->exists();
    }
}
