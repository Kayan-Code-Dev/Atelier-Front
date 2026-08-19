<?php

namespace Database\Seeders\Central;

use App\Models\Central\PlatformPermission;
use App\Models\Central\PlatformRole;
use App\Models\Central\SuperAdmin;
use App\Support\PlatformPermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PlatformPermissionCatalog::definitions() as $def) {
            PlatformPermission::query()->updateOrCreate(
                ['key' => $def['key']],
                [
                    'name' => $def['name'],
                    'module' => $def['module'],
                    'sort_order' => $def['sort_order'],
                ],
            );
        }

        $allKeys = PlatformPermissionCatalog::keys();
        $permissionIds = PlatformPermission::query()->pluck('id', 'key');

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'صلاحية كاملة لكل وحدات لوحة الأدمن',
                'color' => 'teal',
                'is_system' => true,
                'permissions' => $allKeys,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'إدارة الأتيليهات والاشتراكات والباقات والمدفوعات',
                'color' => 'emerald',
                'is_system' => true,
                'permissions' => [
                    'view_dashboard',
                    'view_ateliers', 'add_atelier', 'edit_atelier', 'suspend_atelier',
                    'view_demo_tenants', 'add_demo_tenant', 'delete_demo_tenant', 'promote_demo_tenant',
                    'view_subscriptions', 'edit_subscription', 'delete_subscription', 'cancel_subscription', 'renew_subscription',
                    'view_order_plans', 'approve_order_plan', 'reject_order_plan',
                    'view_payments', 'edit_payment', 'delete_payment', 'mark_paid',
                    'view_payment_gateways',
                    'view_plans', 'add_plan', 'edit_plan',
                    'view_reports', 'export_reports',
                    'view_tickets', 'reply_ticket', 'close_ticket',
                    'view_notifications', 'send_notifications',
                    'view_crm', 'manage_crm', 'manage_crm_leads', 'manage_crm_follow_ups',
                    'manage_crm_deals', 'manage_crm_quotations', 'manage_crm_campaigns',
                    'view_crm_team', 'view_crm_reports', 'manage_crm_settings',
                    'ai_sales.view', 'ai_sales.inbox', 'ai_sales.leads', 'ai_sales.followups',
                    'ai_sales.knowledge', 'ai_sales.agent', 'ai_sales.analytics', 'ai_sales.manage',
                    'view_recruitment', 'manage_recruitment_jobs', 'manage_recruitment_applications', 'manage_recruitment_settings',
                ],
            ],
            [
                'name' => 'Sales Agent',
                'slug' => 'sales',
                'description' => 'فريق مبيعات CRM — Leads ومتابعات وصفقات',
                'color' => 'sky',
                'is_system' => true,
                'permissions' => [
                    'view_dashboard',
                    'view_crm',
                    'manage_crm_leads',
                    'manage_crm_follow_ups',
                    'manage_crm_deals',
                    'manage_crm_quotations',
                    'view_crm_team',
                    'view_crm_reports',
                    'view_plans',
                    'view_ateliers',
                    'ai_sales.view', 'ai_sales.inbox', 'ai_sales.leads', 'ai_sales.followups',
                ],
            ],
            [
                'name' => 'Support Agent',
                'slug' => 'support',
                'description' => 'دعم العملاء وعرض البيانات الأساسية',
                'color' => 'amber',
                'is_system' => true,
                'permissions' => [
                    'view_dashboard',
                    'view_ateliers',
                    'view_subscriptions',
                    'view_order_plans',
                    'view_payments',
                    'view_tickets', 'reply_ticket', 'close_ticket',
                    'view_reports',
                    'view_notifications',
                    'view_crm',
                ],
            ],
            [
                'name' => 'Finance Manager',
                'slug' => 'finance',
                'description' => 'المدفوعات والتقارير المالية',
                'color' => 'green',
                'is_system' => true,
                'permissions' => [
                    'view_dashboard',
                    'view_ateliers',
                    'view_subscriptions', 'edit_subscription', 'delete_subscription', 'renew_subscription',
                    'view_payments', 'edit_payment', 'delete_payment', 'refund_payment', 'mark_paid',
                    'view_payment_gateways',
                    'view_plans',
                    'view_reports', 'export_reports',
                    'view_crm',
                    'view_crm_reports',
                ],
            ],
        ];

        foreach ($roles as $roleDef) {
            $role = PlatformRole::query()->updateOrCreate(
                ['slug' => $roleDef['slug']],
                [
                    'name' => $roleDef['name'],
                    'description' => $roleDef['description'],
                    'color' => $roleDef['color'],
                    'is_system' => $roleDef['is_system'],
                ],
            );

            $ids = [];
            foreach ($roleDef['permissions'] as $key) {
                if (isset($permissionIds[$key])) {
                    $ids[] = $permissionIds[$key];
                }
            }
            $role->permissions()->sync($ids);
        }

        $superRoleId = PlatformRole::query()->where('slug', 'super-admin')->value('id');
        if ($superRoleId) {
            SuperAdmin::query()
                ->whereNull('platform_role_id')
                ->update(['platform_role_id' => $superRoleId]);
        }
    }
}
