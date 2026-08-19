<?php

namespace App\Support;

final class PlatformPermissionCatalog
{
    /**
     * @return list<array{key: string, name: string, module: string, sort_order: int}>
     */
    public static function definitions(): array
    {
        $defs = [
            // dashboard
            ['view_dashboard', 'عرض لوحة التحكم', 'dashboard'],
            // ateliers
            ['view_ateliers', 'عرض الأتيليهات', 'ateliers'],
            ['add_atelier', 'إضافة أتيليه', 'ateliers'],
            ['edit_atelier', 'تعديل أتيليه', 'ateliers'],
            ['suspend_atelier', 'تعليق أتيليه', 'ateliers'],
            ['delete_atelier', 'حذف أتيليه', 'ateliers'],
            ['login_as_atelier', 'الدخول كأتيليه', 'ateliers'],
            // demo tenants
            ['view_demo_tenants', 'عرض الحسابات التجريبية', 'demo_tenants'],
            ['add_demo_tenant', 'إضافة حساب تجريبي', 'demo_tenants'],
            ['delete_demo_tenant', 'حذف حساب تجريبي', 'demo_tenants'],
            ['promote_demo_tenant', 'إضافة اشتراك لحساب تجريبي', 'demo_tenants'],
            // subscriptions
            ['view_subscriptions', 'عرض الاشتراكات', 'subscriptions'],
            ['edit_subscription', 'تعديل اشتراك', 'subscriptions'],
            ['delete_subscription', 'حذف اشتراك', 'subscriptions'],
            ['cancel_subscription', 'إلغاء اشتراك', 'subscriptions'],
            ['renew_subscription', 'تجديد اشتراك', 'subscriptions'],
            // order plans
            ['view_order_plans', 'عرض طلبات الباقات', 'order_plans'],
            ['approve_order_plan', 'الموافقة على طلب باقة', 'order_plans'],
            ['reject_order_plan', 'رفض طلب باقة', 'order_plans'],
            ['delete_order_plan', 'حذف طلب باقة', 'order_plans'],
            // payments
            ['view_payments', 'عرض المدفوعات', 'payments'],
            ['edit_payment', 'تعديل دفعة', 'payments'],
            ['delete_payment', 'حذف دفعة', 'payments'],
            ['refund_payment', 'استرجاع دفعة', 'payments'],
            ['mark_paid', 'تحديد كمدفوع', 'payments'],
            // payment gateways
            ['view_payment_gateways', 'عرض بوابات الدفع', 'payment_gateways'],
            ['add_payment_gateway', 'إضافة بوابة دفع', 'payment_gateways'],
            ['edit_payment_gateway', 'تعديل بوابة دفع', 'payment_gateways'],
            ['delete_payment_gateway', 'حذف بوابة دفع', 'payment_gateways'],
            // plans
            ['view_plans', 'عرض الباقات', 'plans'],
            ['add_plan', 'إضافة باقة', 'plans'],
            ['edit_plan', 'تعديل باقة', 'plans'],
            ['delete_plan', 'حذف باقة', 'plans'],
            // flags
            ['view_flags', 'عرض أعلام الميزات', 'flags'],
            ['edit_flags', 'تعديل أعلام الميزات', 'flags'],
            // support
            ['view_tickets', 'عرض التذاكر', 'support'],
            ['reply_ticket', 'الرد على تذكرة', 'support'],
            ['close_ticket', 'إغلاق تذكرة', 'support'],
            // users
            ['view_users', 'عرض المستخدمين', 'users'],
            ['add_user', 'إضافة مستخدم', 'users'],
            ['edit_user', 'تعديل مستخدم', 'users'],
            ['delete_user', 'حذف مستخدم', 'users'],
            // admin roles
            ['view_admin_roles', 'عرض الأدوار', 'admin_roles'],
            ['add_admin_role', 'إضافة دور', 'admin_roles'],
            ['edit_admin_role', 'تعديل دور', 'admin_roles'],
            ['delete_admin_role', 'حذف دور', 'admin_roles'],
            // reports
            ['view_reports', 'عرض التقارير', 'reports'],
            ['export_reports', 'تصدير التقارير', 'reports'],
            // settings
            ['view_settings', 'عرض الإعدادات', 'settings'],
            ['edit_settings', 'تعديل الإعدادات', 'settings'],
            // notifications
            ['view_notifications', 'عرض الإشعارات', 'notifs'],
            ['send_notifications', 'إرسال إشعارات', 'notifs'],
            // contact messages (homepage form)
            ['view_contact_messages', 'عرض رسائل التواصل', 'contact_messages'],
            // logs
            ['view_logs', 'عرض السجلات', 'logs'],
            // marketing
            ['view_marketing', 'عرض التسويق', 'marketing'],
            ['edit_marketing', 'تعديل التسويق', 'marketing'],
            // CRM & Sales
            ['view_crm', 'عرض CRM والمبيعات', 'crm'],
            ['manage_crm', 'إدارة كاملة لـ CRM', 'crm'],
            ['manage_crm_leads', 'إدارة New Lead', 'crm'],
            ['manage_crm_follow_ups', 'إدارة المتابعات', 'crm'],
            ['manage_crm_deals', 'إدارة الصفقات', 'crm'],
            ['manage_crm_quotations', 'إدارة عروض الأسعار', 'crm'],
            ['manage_crm_campaigns', 'إدارة الحملات', 'crm'],
            ['view_crm_team', 'عرض فريق المبيعات', 'crm'],
            ['view_crm_reports', 'عرض تقارير CRM', 'crm'],
            ['manage_crm_settings', 'إعدادات CRM', 'crm'],
            // integrations
            ['view_integrations', 'عرض التكاملات', 'integrations'],
            // DressnMore AI Sales Agent (platform sales command center)
            ['ai_sales.view', 'عرض وكيل المبيعات الذكي', 'ai_sales'],
            ['ai_sales.inbox', 'صندوق محادثات وكيل المبيعات', 'ai_sales'],
            ['ai_sales.leads', 'عملاء وكيل المبيعات', 'ai_sales'],
            ['ai_sales.followups', 'متابعات وكيل المبيعات', 'ai_sales'],
            ['ai_sales.knowledge', 'قاعدة معرفة وكيل المبيعات', 'ai_sales'],
            ['ai_sales.agent', 'إعدادات وكيل المبيعات', 'ai_sales'],
            ['ai_sales.analytics', 'تحليلات وكيل المبيعات', 'ai_sales'],
            ['ai_sales.manage', 'إدارة وكيل المبيعات', 'ai_sales'],
            ['view_recruitment', 'عرض التوظيف', 'recruitment'],
            ['manage_recruitment_jobs', 'إدارة الوظائف', 'recruitment'],
            ['manage_recruitment_applications', 'إدارة طلبات التوظيف', 'recruitment'],
            ['manage_recruitment_settings', 'إعدادات التوظيف', 'recruitment'],
        ];

        $out = [];
        foreach ($defs as $i => [$key, $name, $module]) {
            $out[] = [
                'key' => $key,
                'name' => $name,
                'module' => $module,
                'sort_order' => $i + 1,
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::definitions(), 'key');
    }

    /**
     * Module lock permission (sidebar / route).
     *
     * @return array<string, string>
     */
    public static function moduleViewMap(): array
    {
        return [
            '/dashboard' => 'view_dashboard',
            '/ateliers' => 'view_ateliers',
            '/demo-tenants' => 'view_demo_tenants',
            '/expired-demo-tenants' => 'view_demo_tenants',
            '/subscriptions' => 'view_subscriptions',
            '/order-plans' => 'view_order_plans',
            '/plans' => 'view_plans',
            '/payments' => 'view_payments',
            '/payment-gateways' => 'view_payment_gateways',
            '/users' => 'view_users',
            '/admin-roles' => 'view_admin_roles',
            '/settings' => 'view_settings',
            '/feature-flags' => 'view_flags',
            '/support' => 'view_tickets',
            '/notifications' => 'view_notifications',
            '/messages' => 'view_dashboard',
            '/logs' => 'view_logs',
            '/integrations' => 'view_integrations',
            '/marketing' => 'view_marketing',
            '/crm' => 'view_crm',
            '/ai-sales' => 'ai_sales.view',
            '/ai-sales/inbox' => 'ai_sales.inbox',
            '/ai-sales/leads' => 'ai_sales.leads',
            '/ai-sales/follow-ups' => 'ai_sales.followups',
            '/ai-sales/knowledge' => 'ai_sales.knowledge',
            '/ai-sales/agent' => 'ai_sales.agent',
            '/ai-sales/analytics' => 'ai_sales.analytics',
            '/recruitment/jobs' => 'view_recruitment',
            '/recruitment/applications' => 'view_recruitment',
            '/recruitment/settings' => 'view_recruitment',
        ];
    }
}
