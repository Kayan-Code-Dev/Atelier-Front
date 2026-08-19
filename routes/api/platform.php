<?php

use App\Http\Controllers\Platform\AuthController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\DemoTenantController;
use App\Http\Controllers\Platform\HealthController;
use App\Http\Controllers\Platform\PaymentController;
use App\Http\Controllers\Platform\PaymentGatewayController;
use App\Http\Controllers\Platform\PlatformBroadcastController;
use App\Http\Controllers\Platform\PlatformNotificationController;
use App\Http\Controllers\Platform\PlanController;
use App\Http\Controllers\Platform\PlanRequestController;
use App\Http\Controllers\Platform\PlatformAdminController;
use App\Http\Controllers\Platform\PlatformRoleController;
use App\Http\Controllers\Platform\SubscriptionController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Platform\CrmController;
use App\Http\Controllers\Platform\ContactMessageController;
use App\Http\Controllers\Platform\LandingSettingsController;
use App\Http\Controllers\Platform\AiSalesController;
use App\Http\Controllers\Platform\RecruitmentJobController;
use App\Http\Controllers\Platform\RecruitmentApplicationController;
use App\Http\Controllers\Platform\RecruitmentSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->group(function (): void {
    Route::get('/health', [HealthController::class, 'index']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/plans/public', [PlanController::class, 'publicIndex']);

    Route::get('/payment-gateways/public', [PlanRequestController::class, 'paymentGateways']);
    Route::post('/plan-requests', [PlanRequestController::class, 'store']);

    Route::middleware(['auth:sanctum', 'platform.admin'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::post('/me/avatar', [AuthController::class, 'uploadAvatar']);
        Route::delete('/me/avatar', [AuthController::class, 'deleteAvatar']);
        Route::post('/change-password', [PlatformAdminController::class, 'changePassword']);

        // Platform broadcasts to tenants
        Route::get('/broadcasts/summary', [PlatformBroadcastController::class, 'summary'])
            ->middleware('platform.permission:view_notifications');
        Route::get('/broadcasts', [PlatformBroadcastController::class, 'index'])
            ->middleware('platform.permission:view_notifications');
        Route::get('/broadcasts/{id}', [PlatformBroadcastController::class, 'show'])
            ->whereNumber('id')
            ->middleware('platform.permission:view_notifications');
        Route::post('/broadcasts', [PlatformBroadcastController::class, 'store'])
            ->middleware('platform.permission:send_notifications');

        Route::get('/notifications/stats', [PlatformNotificationController::class, 'stats'])
            ->middleware('platform.permission:view_notifications');
        Route::get('/notifications', [PlatformNotificationController::class, 'index'])
            ->middleware('platform.permission:view_notifications');
        Route::post('/notifications/read-all', [PlatformNotificationController::class, 'markAllRead'])
            ->middleware('platform.permission:view_notifications');
        Route::patch('/notifications/{notification}/read', [PlatformNotificationController::class, 'markRead'])
            ->whereNumber('notification')
            ->middleware('platform.permission:view_notifications');
        Route::delete('/notifications/{notification}', [PlatformNotificationController::class, 'destroy'])
            ->whereNumber('notification')
            ->middleware('platform.permission:view_notifications');

        Route::get('/dashboard/summary', [DashboardController::class, 'summary'])
            ->middleware('platform.permission:view_dashboard');

        // Plans
        Route::get('/plans/feature-catalog', [PlanController::class, 'featureCatalog'])
            ->middleware('platform.permission:view_plans');
        Route::get('/plans', [PlanController::class, 'index'])
            ->middleware('platform.permission:view_plans');
        Route::post('/plans', [PlanController::class, 'store'])
            ->middleware('platform.permission:add_plan');
        Route::get('/plans/{plan}', [PlanController::class, 'show'])
            ->whereNumber('plan')
            ->middleware('platform.permission:view_plans');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])
            ->whereNumber('plan')
            ->middleware('platform.permission:edit_plan');
        Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])
            ->whereNumber('plan')
            ->middleware('platform.permission:delete_plan');

        // Tenants / Ateliers
        Route::get('/tenants', [TenantController::class, 'index'])
            ->middleware('platform.permission:view_ateliers');
        Route::post('/tenants', [TenantController::class, 'store'])
            ->middleware('platform.permission:add_atelier');
        Route::get('/tenants/{tenant}', [TenantController::class, 'show'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:view_ateliers');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:edit_atelier');
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:delete_atelier');
        Route::post('/tenants/{tenant}/migrate', [TenantController::class, 'migrate'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:edit_atelier');
        Route::post('/tenants/{tenant}/seed', [TenantController::class, 'seed'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:edit_atelier');
        Route::post('/tenants/{tenant}/domains', [TenantController::class, 'addDomain'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:edit_atelier');
        Route::delete('/tenants/{tenant}/domains/{domain}', [TenantController::class, 'deleteDomain'])
            ->whereNumber(['tenant', 'domain'])
            ->middleware('platform.permission:edit_atelier');
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:suspend_atelier');
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:suspend_atelier');
        Route::post('/tenants/{tenant}/renew', [TenantController::class, 'renew'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:renew_subscription');
        Route::post('/tenants/{tenant}/login-as', [TenantController::class, 'loginAs'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:login_as_atelier');

        // Demo / trial tenants (no plan, 1–7 days)
        Route::get('/demo-tenants', [DemoTenantController::class, 'index'])
            ->middleware('platform.permission:view_demo_tenants');
        Route::get('/demo-tenants/expired', [DemoTenantController::class, 'expired'])
            ->middleware('platform.permission:view_demo_tenants');
        Route::post('/demo-tenants', [DemoTenantController::class, 'store'])
            ->middleware('platform.permission:add_demo_tenant');
        Route::get('/demo-tenants/{tenant}', [DemoTenantController::class, 'show'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:view_demo_tenants');
        Route::get('/demo-tenants/{tenant}/performance', [DemoTenantController::class, 'performance'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:view_demo_tenants');
        Route::post('/demo-tenants/{tenant}/promote', [DemoTenantController::class, 'promote'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:promote_demo_tenant');
        Route::delete('/demo-tenants/{tenant}', [DemoTenantController::class, 'destroy'])
            ->whereNumber('tenant')
            ->middleware('platform.permission:delete_demo_tenant');

        // Payment gateways
        Route::get('/payment-gateways', [PaymentGatewayController::class, 'index'])
            ->middleware('platform.permission:view_payment_gateways');
        Route::post('/payment-gateways', [PaymentGatewayController::class, 'store'])
            ->middleware('platform.permission:add_payment_gateway');
        Route::get('/payment-gateways/{paymentGateway}', [PaymentGatewayController::class, 'show'])
            ->whereNumber('paymentGateway')
            ->middleware('platform.permission:view_payment_gateways');
        Route::put('/payment-gateways/{paymentGateway}', [PaymentGatewayController::class, 'update'])
            ->whereNumber('paymentGateway')
            ->middleware('platform.permission:edit_payment_gateway');
        Route::delete('/payment-gateways/{paymentGateway}', [PaymentGatewayController::class, 'destroy'])
            ->whereNumber('paymentGateway')
            ->middleware('platform.permission:delete_payment_gateway');

        // Subscriptions
        Route::get('/subscriptions', [SubscriptionController::class, 'index'])
            ->middleware('platform.permission:view_subscriptions');
        Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show'])
            ->middleware('platform.permission:view_subscriptions');
        Route::patch('/subscriptions/{id}', [SubscriptionController::class, 'update'])
            ->middleware('platform.permission:edit_subscription');
        Route::post('/subscriptions/{id}/renew', [SubscriptionController::class, 'renew'])
            ->whereNumber('id')
            ->middleware('platform.permission:renew_subscription');
        Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('platform.permission:delete_subscription');

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])
            ->middleware('platform.permission:view_payments');
        Route::get('/payments/{id}', [PaymentController::class, 'show'])
            ->whereNumber('id')
            ->middleware('platform.permission:view_payments');
        Route::patch('/payments/{id}', [PaymentController::class, 'update'])
            ->whereNumber('id')
            ->middleware('platform.permission:edit_payment');
        Route::delete('/payments/{id}', [PaymentController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('platform.permission:delete_payment');

        // Order Plans
        Route::get('/order-plans', [PlanRequestController::class, 'index'])
            ->middleware('platform.permission:view_order_plans');
        Route::get('/order-plans/{id}', [PlanRequestController::class, 'show'])
            ->middleware('platform.permission:view_order_plans');
        Route::patch('/order-plans/{id}', [PlanRequestController::class, 'update'])
            ->middleware('platform.permission:approve_order_plan');
        Route::post('/order-plans/{id}/approve', [PlanRequestController::class, 'approve'])
            ->middleware('platform.permission:approve_order_plan');
        Route::post('/order-plans/{id}/reject', [PlanRequestController::class, 'reject'])
            ->middleware('platform.permission:reject_order_plan');
        Route::delete('/order-plans/{id}', [PlanRequestController::class, 'destroy'])
            ->middleware('platform.permission:delete_order_plan');

        // Platform admins (users)
        Route::get('/admins', [PlatformAdminController::class, 'index'])
            ->middleware('platform.permission:view_users');
        Route::post('/admins', [PlatformAdminController::class, 'store'])
            ->middleware('platform.permission:add_user');
        Route::get('/admins/{id}', [PlatformAdminController::class, 'show'])
            ->whereNumber('id')
            ->middleware('platform.permission:view_users');
        Route::put('/admins/{id}', [PlatformAdminController::class, 'update'])
            ->whereNumber('id')
            ->middleware('platform.permission:edit_user');
        Route::delete('/admins/{id}', [PlatformAdminController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('platform.permission:delete_user');
        Route::post('/admins/{id}/suspend', [PlatformAdminController::class, 'suspend'])
            ->whereNumber('id')
            ->middleware('platform.permission:edit_user');
        Route::post('/admins/{id}/activate', [PlatformAdminController::class, 'activate'])
            ->whereNumber('id')
            ->middleware('platform.permission:edit_user');

        // Roles & permissions
        Route::get('/permissions', [PlatformRoleController::class, 'permissions'])
            ->middleware('platform.permission:view_admin_roles');
        Route::get('/roles', [PlatformRoleController::class, 'index'])
            ->middleware('platform.permission:view_admin_roles,view_users');
        Route::post('/roles', [PlatformRoleController::class, 'store'])
            ->middleware('platform.permission:add_admin_role');
        Route::get('/roles/{id}', [PlatformRoleController::class, 'show'])
            ->whereNumber('id')
            ->middleware('platform.permission:view_admin_roles');
        Route::put('/roles/{id}', [PlatformRoleController::class, 'update'])
            ->whereNumber('id')
            ->middleware('platform.permission:edit_admin_role');
        Route::delete('/roles/{id}', [PlatformRoleController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('platform.permission:delete_admin_role');

        // Homepage / landing CMS
        Route::get('/landing-settings', [LandingSettingsController::class, 'show'])
            ->middleware('platform.permission:view_settings');
        Route::put('/landing-settings', [LandingSettingsController::class, 'update'])
            ->middleware('platform.permission:edit_settings');

        // Contact form inbox (homepage messages)
        Route::get('/contact-messages/unread-count', [ContactMessageController::class, 'unreadCount'])
            ->middleware('platform.permission:view_contact_messages,view_dashboard');
        Route::get('/contact-messages', [ContactMessageController::class, 'index'])
            ->middleware('platform.permission:view_contact_messages,view_dashboard');
        Route::post('/contact-messages/read-all', [ContactMessageController::class, 'markAllRead'])
            ->middleware('platform.permission:view_contact_messages,view_dashboard');
        Route::get('/contact-messages/{id}', [ContactMessageController::class, 'show'])
            ->whereNumber('id')
            ->middleware('platform.permission:view_contact_messages,view_dashboard');
        Route::post('/contact-messages/{id}/read', [ContactMessageController::class, 'markRead'])
            ->whereNumber('id')
            ->middleware('platform.permission:view_contact_messages,view_dashboard');

        // CRM & Sales
        Route::get('/crm/dashboard', [CrmController::class, 'dashboard'])
            ->middleware('platform.permission:view_crm');
        Route::post('/crm/bootstrap', [CrmController::class, 'bootstrap'])
            ->middleware('platform.permission:manage_crm,manage_crm_settings');

        Route::get('/crm/leads', [CrmController::class, 'leads'])
            ->middleware('platform.permission:view_crm');
        Route::post('/crm/leads', [CrmController::class, 'storeLead'])
            ->middleware('platform.permission:manage_crm,manage_crm_leads');
        Route::get('/crm/leads/{id}', [CrmController::class, 'showLead'])
            ->whereNumber('id')
            ->middleware('platform.permission:view_crm');
        Route::put('/crm/leads/{id}', [CrmController::class, 'updateLead'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_leads');
        Route::delete('/crm/leads/{id}', [CrmController::class, 'destroyLead'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_leads');
        Route::post('/crm/leads/{id}/notes', [CrmController::class, 'addNote'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_leads');
        Route::post('/crm/leads/{id}/events', [CrmController::class, 'addEvent'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_leads');
        Route::post('/crm/leads/{id}/attachments', [CrmController::class, 'addAttachment'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_leads');
        Route::post('/crm/leads/{id}/appointments', [CrmController::class, 'scheduleLeadAppointment'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_follow_ups,manage_crm_leads');
        Route::post('/crm/leads/{id}/open-deal', [CrmController::class, 'openLeadDeal'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_deals,manage_crm_leads');

        Route::get('/crm/follow-ups', [CrmController::class, 'followUps'])
            ->middleware('platform.permission:view_crm');
        Route::post('/crm/follow-ups', [CrmController::class, 'storeFollowUp'])
            ->middleware('platform.permission:manage_crm,manage_crm_follow_ups');
        Route::patch('/crm/follow-ups/{id}', [CrmController::class, 'updateFollowUp'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_follow_ups');
        Route::delete('/crm/follow-ups/{id}', [CrmController::class, 'destroyFollowUp'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_follow_ups');

        Route::get('/crm/deals', [CrmController::class, 'deals'])
            ->middleware('platform.permission:view_crm');
        Route::post('/crm/deals', [CrmController::class, 'storeDeal'])
            ->middleware('platform.permission:manage_crm,manage_crm_deals');
        Route::patch('/crm/deals/{id}', [CrmController::class, 'updateDeal'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_deals');
        Route::delete('/crm/deals/{id}', [CrmController::class, 'destroyDeal'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_deals');

        Route::get('/crm/quotations', [CrmController::class, 'quotations'])
            ->middleware('platform.permission:view_crm');
        Route::post('/crm/quotations', [CrmController::class, 'storeQuotation'])
            ->middleware('platform.permission:manage_crm,manage_crm_quotations');
        Route::put('/crm/quotations/{id}', [CrmController::class, 'updateQuotation'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_quotations');
        Route::delete('/crm/quotations/{id}', [CrmController::class, 'destroyQuotation'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_quotations');

        Route::get('/crm/campaigns', [CrmController::class, 'campaigns'])
            ->middleware('platform.permission:view_crm');
        Route::post('/crm/campaigns', [CrmController::class, 'storeCampaign'])
            ->middleware('platform.permission:manage_crm,manage_crm_campaigns');
        Route::put('/crm/campaigns/{id}', [CrmController::class, 'updateCampaign'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_campaigns');
        Route::delete('/crm/campaigns/{id}', [CrmController::class, 'destroyCampaign'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm,manage_crm_campaigns');

        Route::get('/crm/team', [CrmController::class, 'team'])
            ->middleware('platform.permission:view_crm,view_crm_team');
        Route::get('/crm/reports', [CrmController::class, 'reports'])
            ->middleware('platform.permission:view_crm,view_crm_reports');

        Route::get('/crm/settings', [CrmController::class, 'settings'])
            ->middleware('platform.permission:view_crm,manage_crm_settings');
        Route::put('/crm/settings', [CrmController::class, 'updateSettings'])
            ->middleware('platform.permission:manage_crm_settings,manage_crm');
        Route::post('/crm/lookups', [CrmController::class, 'storeLookup'])
            ->middleware('platform.permission:manage_crm_settings,manage_crm');
        Route::put('/crm/lookups/{id}', [CrmController::class, 'updateLookup'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm_settings,manage_crm');
        Route::delete('/crm/lookups/{id}', [CrmController::class, 'destroyLookup'])
            ->whereNumber('id')
            ->middleware('platform.permission:manage_crm_settings,manage_crm');

        // DressnMore AI Sales Agent — command center (no LLM / no channels)
        Route::prefix('ai-sales')->group(function (): void {
            Route::get('/overview', [AiSalesController::class, 'overview'])
                ->middleware('platform.permission:ai_sales.view');
            Route::get('/analytics', [AiSalesController::class, 'analytics'])
                ->middleware('platform.permission:ai_sales.analytics,ai_sales.view');
            Route::get('/context', [AiSalesController::class, 'context'])
                ->middleware('platform.permission:ai_sales.view,ai_sales.agent');
            Route::get('/conversations', [AiSalesController::class, 'conversations'])
                ->middleware('platform.permission:ai_sales.inbox,ai_sales.view');
            Route::post('/conversations', [AiSalesController::class, 'storeConversation'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.inbox');
            Route::get('/conversations/{id}', [AiSalesController::class, 'conversation'])
                ->middleware('platform.permission:ai_sales.inbox,ai_sales.view');
            Route::post('/conversations/{id}/messages', [AiSalesController::class, 'addMessage'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.inbox');
            Route::patch('/conversations/{id}/handoff', [AiSalesController::class, 'updateHandoff'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.inbox');
            Route::post('/conversations/{id}/reset-session', [AiSalesController::class, 'resetSession'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.inbox');
            Route::post('/recommend', [AiSalesController::class, 'recommend'])
                ->middleware('platform.permission:ai_sales.view,ai_sales.agent');
            Route::post('/simulate', [AiSalesController::class, 'simulate'])
                ->middleware('platform.permission:ai_sales.agent,ai_sales.manage,ai_sales.view');
            Route::get('/leads', [AiSalesController::class, 'leads'])
                ->middleware('platform.permission:ai_sales.leads,ai_sales.view');
            Route::post('/leads', [AiSalesController::class, 'storeLead'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.leads');
            Route::get('/leads/{id}', [AiSalesController::class, 'lead'])
                ->whereNumber('id')
                ->middleware('platform.permission:ai_sales.leads,ai_sales.view');
            Route::patch('/leads/{id}', [AiSalesController::class, 'updateLead'])
                ->whereNumber('id')
                ->middleware('platform.permission:ai_sales.manage,ai_sales.leads');
            Route::get('/follow-ups', [AiSalesController::class, 'followUps'])
                ->middleware('platform.permission:ai_sales.followups,ai_sales.view');
            Route::post('/follow-ups', [AiSalesController::class, 'storeFollowUp'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.followups');
            Route::patch('/follow-ups/{id}', [AiSalesController::class, 'updateFollowUp'])
                ->whereNumber('id')
                ->middleware('platform.permission:ai_sales.manage,ai_sales.followups');
            Route::get('/agent', [AiSalesController::class, 'agent'])
                ->middleware('platform.permission:ai_sales.agent,ai_sales.view');
            Route::put('/agent', [AiSalesController::class, 'updateAgent'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.agent');
            Route::patch('/agent', [AiSalesController::class, 'updateAgent'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.agent');
            Route::get('/knowledge', [AiSalesController::class, 'knowledge'])
                ->middleware('platform.permission:ai_sales.knowledge,ai_sales.view');
            Route::post('/knowledge', [AiSalesController::class, 'storeKnowledge'])
                ->middleware('platform.permission:ai_sales.manage,ai_sales.knowledge');
            Route::put('/knowledge/{id}', [AiSalesController::class, 'updateKnowledge'])
                ->whereNumber('id')
                ->middleware('platform.permission:ai_sales.manage,ai_sales.knowledge');
            Route::patch('/knowledge/{id}', [AiSalesController::class, 'updateKnowledge'])
                ->whereNumber('id')
                ->middleware('platform.permission:ai_sales.manage,ai_sales.knowledge');
            Route::delete('/knowledge/{id}', [AiSalesController::class, 'destroyKnowledge'])
                ->whereNumber('id')
                ->middleware('platform.permission:ai_sales.manage,ai_sales.knowledge');
        });

        Route::prefix('recruitment')->group(function (): void {
            Route::get('/jobs', [RecruitmentJobController::class, 'index'])
                ->middleware('platform.permission:view_recruitment,manage_recruitment_jobs');
            Route::post('/jobs', [RecruitmentJobController::class, 'store'])
                ->middleware('platform.permission:manage_recruitment_jobs');
            Route::get('/jobs/{id}', [RecruitmentJobController::class, 'show'])
                ->whereNumber('id')
                ->middleware('platform.permission:view_recruitment,manage_recruitment_jobs');
            Route::put('/jobs/{id}', [RecruitmentJobController::class, 'update'])
                ->whereNumber('id')
                ->middleware('platform.permission:manage_recruitment_jobs');
            Route::delete('/jobs/{id}', [RecruitmentJobController::class, 'destroy'])
                ->whereNumber('id')
                ->middleware('platform.permission:manage_recruitment_jobs');
            Route::post('/jobs/{id}/publish', [RecruitmentJobController::class, 'publish'])
                ->whereNumber('id')
                ->middleware('platform.permission:manage_recruitment_jobs');
            Route::post('/jobs/{id}/hide', [RecruitmentJobController::class, 'hide'])
                ->whereNumber('id')
                ->middleware('platform.permission:manage_recruitment_jobs');
            Route::post('/jobs/{id}/close', [RecruitmentJobController::class, 'close'])
                ->whereNumber('id')
                ->middleware('platform.permission:manage_recruitment_jobs');
            Route::post('/jobs/{id}/archive', [RecruitmentJobController::class, 'archive'])
                ->whereNumber('id')
                ->middleware('platform.permission:manage_recruitment_jobs');

            Route::get('/applications/summary', [RecruitmentApplicationController::class, 'summary'])
                ->middleware('platform.permission:view_recruitment,manage_recruitment_applications');
            Route::get('/applications', [RecruitmentApplicationController::class, 'index'])
                ->middleware('platform.permission:view_recruitment,manage_recruitment_applications');
            Route::get('/applications/{id}', [RecruitmentApplicationController::class, 'show'])
                ->whereNumber('id')
                ->middleware('platform.permission:view_recruitment,manage_recruitment_applications');
            Route::patch('/applications/{id}/status', [RecruitmentApplicationController::class, 'updateStatus'])
                ->whereNumber('id')
                ->middleware('platform.permission:manage_recruitment_applications');
            Route::post('/applications/{id}/notes', [RecruitmentApplicationController::class, 'addNote'])
                ->whereNumber('id')
                ->middleware('platform.permission:manage_recruitment_applications');
            Route::get('/applications/{id}/cv', [RecruitmentApplicationController::class, 'downloadCv'])
                ->whereNumber('id')
                ->middleware('platform.permission:view_recruitment,manage_recruitment_applications');

            Route::get('/settings', [RecruitmentSettingsController::class, 'show'])
                ->middleware('platform.permission:view_recruitment,manage_recruitment_settings');
            Route::put('/settings', [RecruitmentSettingsController::class, 'update'])
                ->middleware('platform.permission:manage_recruitment_settings');
        });
    });
});
