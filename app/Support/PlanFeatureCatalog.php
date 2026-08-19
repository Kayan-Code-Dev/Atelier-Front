<?php

namespace App\Support;

/**
 * Central catalog of commercial plan features and limits.
 *
 * Source of truth for Free / Starter / Professional / Business entitlements.
 */
class PlanFeatureCatalog
{
    public const PLAN_FREE = 'free';

    public const PLAN_STARTER = 'starter';

    public const PLAN_PROFESSIONAL = 'professional';

    public const PLAN_BUSINESS = 'business';

    /**
     * Legacy slugs kept for existing tenants until remapped.
     *
     * @var array<string, string>
     */
    public const LEGACY_SLUG_ALIASES = [
        'basic' => self::PLAN_STARTER,
        'pro' => self::PLAN_PROFESSIONAL,
        'enterprise' => self::PLAN_BUSINESS,
    ];

    /**
     * Public commercial plan order (lowest → highest).
     *
     * @return list<string>
     */
    public static function publicPlanSlugs(): array
    {
        return [
            self::PLAN_FREE,
            self::PLAN_STARTER,
            self::PLAN_PROFESSIONAL,
            self::PLAN_BUSINESS,
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function planRank(): array
    {
        return [
            self::PLAN_FREE => 0,
            self::PLAN_STARTER => 1,
            self::PLAN_PROFESSIONAL => 2,
            self::PLAN_BUSINESS => 3,
            'basic' => 1,
            'pro' => 2,
            'enterprise' => 3,
            'demo' => 99,
            'custom' => 99,
        ];
    }

    public static function normalizePlanSlug(?string $slug): string
    {
        $slug = strtolower(trim((string) $slug));
        if ($slug === '' || $slug === 'demo') {
            return $slug === 'demo' ? 'demo' : self::PLAN_FREE;
        }

        if ($slug === 'custom') {
            return 'custom';
        }

        return self::LEGACY_SLUG_ALIASES[$slug] ?? $slug;
    }

    public static function planRankOf(?string $slug): int
    {
        $normalized = self::normalizePlanSlug($slug);

        return self::planRank()[$normalized] ?? self::planRank()[self::PLAN_FREE];
    }

    /**
     * @return list<array{
     *   key: string,
     *   label: string,
     *   group: string,
     *   category: string,
     *   type: string,
     *   feature_type: string,
     *   description: string,
     *   minimum_plan: string,
     *   upgrade_message: string,
     *   limit_type: string|null
     * }>
     */
    public static function definitions(): array
    {
        return [
            self::boolFeature('dashboard.enabled', 'لوحة التحكم', 'core', 'operations', 'نظرة عامة ومؤشرات الأداء', self::PLAN_FREE, 'لوحة التحكم متاحة في كل الباقات.'),
            self::boolFeature('customers.enabled', 'إدارة العملاء', 'core', 'customers', 'إدارة بيانات العملاء وسجلاتهم', self::PLAN_FREE, 'إدارة العملاء متاحة من الباقة المجانية.'),
            self::boolFeature('categories.enabled', 'الأقسام', 'catalog', 'catalog', 'أقسام المنتجات', self::PLAN_FREE, 'الأقسام متاحة من الباقة المجانية.'),
            self::boolFeature('subcategories.enabled', 'الأقسام الفرعية', 'catalog', 'catalog', 'التصنيفات الفرعية للمنتجات', self::PLAN_FREE, 'الأقسام الفرعية متاحة من الباقة المجانية.'),
            self::boolFeature('dresses.enabled', 'المنتجات والمخزون', 'catalog', 'catalog', 'كتالوج المنتجات والفساتين', self::PLAN_FREE, 'كتالوج المنتجات متاح من الباقة المجانية.'),
            self::boolFeature('inventory.enabled', 'حركات المخزون', 'catalog', 'catalog', 'تحويلات وحركات المخزون بين الفروع', self::PLAN_STARTER, 'فعّل حركات المخزون المتقدمة مع باقة Starter لإدارة التحويلات بدقة.'),
            self::boolFeature('branches.enabled', 'الفروع', 'operations', 'organization', 'إدارة الفروع والحدود حسب الباقة', self::PLAN_FREE, 'إدارة الفروع متاحة حسب حدود باقتك.'),
            self::boolFeature('invoices.enabled', 'الفواتير', 'sales', 'operations', 'فواتير الإيجار والبيع والتفصيل', self::PLAN_FREE, 'الفواتير متاحة من الباقة المجانية ضمن الكوتة الشهرية.'),
            self::boolFeature('orders.enabled', 'طلبات الإيجار', 'sales', 'operations', 'إدارة طلبات وحجوزات الإيجار', self::PLAN_FREE, 'طلبات الإيجار متاحة من الباقة المجانية.'),
            self::boolFeature('payments.enabled', 'المدفوعات', 'sales', 'finance', 'مدفوعات الفواتير والتحصيل', self::PLAN_FREE, 'المدفوعات متاحة من الباقة المجانية.'),
            self::boolFeature('deliveries.enabled', 'التسليم', 'sales', 'operations', 'سير عمل تسليم الإيجار', self::PLAN_FREE, 'التسليمات متاحة من الباقة المجانية.'),
            self::boolFeature('returns.enabled', 'المرتجعات', 'sales', 'operations', 'سير عمل إرجاع الإيجار', self::PLAN_FREE, 'المرتجعات متاحة من الباقة المجانية.'),
            self::boolFeature('suppliers.enabled', 'الموردون', 'purchasing', 'catalog', 'حسابات الموردين والمشتريات', self::PLAN_STARTER, 'أدر الموردين والمشتريات باحتراف مع باقة Starter.'),
            self::boolFeature('purchase_orders.enabled', 'أوامر الشراء', 'purchasing', 'catalog', 'أوامر شراء الموردين', self::PLAN_STARTER, 'أنشئ أوامر شراء منظمة مع باقة Starter.'),
            self::boolFeature('supplier_payments.enabled', 'مدفوعات الموردين', 'purchasing', 'finance', 'سداد مستحقات الموردين', self::PLAN_PROFESSIONAL, 'سداد الموردين المتقدم متاح من باقة Professional.'),
            self::boolFeature('expenses.enabled', 'المصروفات', 'finance', 'finance', 'إدارة مصروفات الأتيليه', self::PLAN_FREE, 'المصروفات متاحة من الباقة المجانية.'),
            self::boolFeature('cashboxes.enabled', 'الخزن', 'finance', 'finance', 'أرصدة الخزن', self::PLAN_FREE, 'الخزن متاحة من الباقة المجانية.'),
            self::boolFeature('cash_movements.enabled', 'حركات النقدية', 'finance', 'finance', 'قيود نقدية يدوية', self::PLAN_FREE, 'حركات النقدية متاحة من الباقة المجانية.'),
            self::boolFeature('reports.enabled', 'التقارير', 'analytics', 'intelligence', 'تقارير المبيعات والتفصيل والأداء', self::PLAN_STARTER, 'احصل على تقارير تشغيلية قياسية مع باقة Starter.'),
            self::boolFeature('accounting.enabled', 'المحاسبة', 'analytics', 'finance', 'ملخص المحاسبة والدفتر والقيود', self::PLAN_PROFESSIONAL, 'المحاسبة المتقدمة متاحة من باقة Professional.'),
            self::boolFeature('ai_assistant.enabled', 'المستشار الذكي', 'intelligence', 'intelligence', 'تحليل المبيعات والعملاء والفروع بالذكاء الاصطناعي', self::PLAN_PROFESSIONAL, 'افهم أداء أتيلييهك بالتحليل الذكي — متاح من باقة Professional.'),
            self::boolFeature('ai_assistant.advanced', 'المستشار الذكي المتقدم', 'intelligence', 'intelligence', 'تحليل أعمق وإجابات شاملة ورؤى مالية متقدمة', self::PLAN_PROFESSIONAL, 'الرؤى المتقدمة للمستشار الذكي متاحة من باقة Professional.'),
            self::boolFeature('smart_assistant.enabled', 'المساعد الذكي', 'automation', 'digital', 'ربط واتساب وفيسبوك وإنستغرام للرد على الرسائل والتعليقات', self::PLAN_STARTER, 'اربط قنواتك الاجتماعية ورد على العملاء مع باقة Starter.'),
            self::boolFeature('smart_assistant.auto_reply', 'الرد الآلي للمساعد الذكي', 'automation', 'digital', 'تفعيل الردود التلقائية على الرسائل والتعليقات', self::PLAN_STARTER, 'الرد الآلي للمساعد الذكي يُفعَّل من الباقة ويُضبط بعدد الرسائل الشهرية.'),
            self::limitFeature('smart_assistant.messages_monthly.max', 'كوتة رسائل المساعد الذكي الشهرية', 'عدد ردود المساعد على واتساب والقنوات لكل شهر — 0 = غير محدود. عند النفاد يتوقف المساعد عن الرد.'),
            self::boolFeature('website.enabled', 'الموقع الإلكتروني', 'digital', 'digital', 'بناء ونشر موقع الأتيليه والقوالب والحجوزات', self::PLAN_STARTER, 'أنشئ موقع أتيلييه احترافي وانشره مع باقة Starter.'),
            self::boolFeature('marketplace.enabled', 'الماركت بليس', 'digital', 'digital', 'لوحة الماركت ومتجر السوق على market.dressnmore.it.com مع الفلترة حسب الأقرب', self::PLAN_STARTER, 'انشر منتجاتك في الماركت بليس مع باقة Starter.'),
            self::boolFeature('hr.enabled', 'الموارد البشرية', 'organization', 'organization', 'الموظفون والحضور والإجازات والرواتب', self::PLAN_STARTER, 'أدر فريقك بالكامل مع وحدة HR في باقة Starter.'),
            self::boolFeature('workshop.enabled', 'الورشة', 'operations', 'operations', 'إدارة الورشة وسير عمل التفصيل', self::PLAN_STARTER, 'لوحة الورشة المتقدمة متاحة من باقة Starter.'),
            self::boolFeature('factory.enabled', 'المصنع', 'operations', 'operations', 'إدارة عمليات المصنع والإنتاج', self::PLAN_BUSINESS, 'وحدة المصنع للمؤسسات متاحة في باقة Business.'),
            self::limitFeature('branches.max', 'الحد الأقصى للفروع', 'حتى عدد محدد من الفروع — 0 = غير محدود'),
            self::limitFeature('users.max', 'الحد الأقصى للموظفين', 'عدد حسابات الدخول — 0 = غير محدود'),
            self::limitFeature('invoices.monthly.max', 'كوتة الفواتير الشهرية الإجمالية', 'إجمالي فواتير البيع والإيجار والتفصيل لكل شهر — 0 = غير محدود'),
            self::limitFeature('invoices.sale.max', 'كوتة فواتير البيع الشهرية', 'لكل شهر ميلادي — 0 = غير محدود'),
            self::limitFeature('invoices.rent.max', 'كوتة فواتير الإيجار الشهرية', 'لكل شهر ميلادي — 0 = غير محدود'),
            self::limitFeature('invoices.tailoring.max', 'كوتة فواتير التفصيل الشهرية', 'لكل شهر ميلادي — 0 = غير محدود'),
            self::limitFeature('ai_assistant.chat_monthly.max', 'كوتة رسائل الشات الشهرية', 'رسائل المستخدم في المستشار الذكي لكل شهر — 0 = غير محدود'),
        ];
    }

    /**
     * Default feature matrix for the four public plans.
     *
     * @return array<string, array<string, bool|int>>
     */
    public static function defaultMatrix(): array
    {
        $allFalse = [];
        foreach (self::definitions() as $definition) {
            $key = $definition['key'];
            $allFalse[$key] = self::isBooleanKey($key) ? false : 0;
        }

        $free = array_merge($allFalse, [
            'dashboard.enabled' => true,
            'customers.enabled' => true,
            'categories.enabled' => true,
            'subcategories.enabled' => true,
            'dresses.enabled' => true,
            'branches.enabled' => true,
            'invoices.enabled' => true,
            'orders.enabled' => true,
            'payments.enabled' => true,
            'deliveries.enabled' => true,
            'returns.enabled' => true,
            'expenses.enabled' => true,
            'cashboxes.enabled' => true,
            'cash_movements.enabled' => true,
            'branches.max' => 1,
            'users.max' => 1,
            'invoices.monthly.max' => 15,
            'invoices.sale.max' => 15,
            'invoices.rent.max' => 15,
            'invoices.tailoring.max' => 15,
            'ai_assistant.chat_monthly.max' => 0,
            'smart_assistant.messages_monthly.max' => 50,
        ]);

        $starter = array_merge($free, [
            'inventory.enabled' => true,
            'suppliers.enabled' => true,
            'purchase_orders.enabled' => true,
            'reports.enabled' => true,
            'smart_assistant.enabled' => true,
            'smart_assistant.auto_reply' => true,
            'website.enabled' => true,
            'marketplace.enabled' => true,
            'hr.enabled' => true,
            'workshop.enabled' => true,
            'branches.max' => 1,
            'users.max' => 3,
            'invoices.monthly.max' => 0,
            'invoices.sale.max' => 0,
            'invoices.rent.max' => 0,
            'invoices.tailoring.max' => 0,
            'ai_assistant.chat_monthly.max' => 200,
            'smart_assistant.messages_monthly.max' => 300,
        ]);

        $professional = array_merge($starter, [
            'supplier_payments.enabled' => true,
            'accounting.enabled' => true,
            'ai_assistant.enabled' => true,
            'ai_assistant.advanced' => true,
            'branches.max' => 3,
            'users.max' => 10,
            'ai_assistant.chat_monthly.max' => 1000,
            'smart_assistant.messages_monthly.max' => 1500,
        ]);

        $business = array_merge($professional, [
            'smart_assistant.auto_reply' => true,
            'factory.enabled' => true,
            'branches.max' => 0,
            'users.max' => 0,
            'ai_assistant.chat_monthly.max' => 0,
            'smart_assistant.messages_monthly.max' => 0,
        ]);

        return [
            self::PLAN_FREE => $free,
            self::PLAN_STARTER => $starter,
            self::PLAN_PROFESSIONAL => $professional,
            self::PLAN_BUSINESS => $business,
            // Legacy aliases keep existing tenants working until remapped.
            'basic' => $starter,
            'pro' => $professional,
            'enterprise' => $business,
        ];
    }

    /**
     * @return array{
     *   key: string,
     *   label: string,
     *   group: string,
     *   category: string,
     *   type: string,
     *   feature_type: string,
     *   description: string,
     *   minimum_plan: string,
     *   upgrade_message: string,
     *   limit_type: null
     * }
     */
    private static function boolFeature(
        string $key,
        string $label,
        string $group,
        string $category,
        string $description,
        string $minimumPlan,
        string $upgradeMessage,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'group' => $group,
            'category' => $category,
            'type' => 'boolean',
            'feature_type' => 'module',
            'description' => $description,
            'minimum_plan' => $minimumPlan,
            'upgrade_message' => $upgradeMessage,
            'limit_type' => null,
        ];
    }

    /**
     * @return array{
     *   key: string,
     *   label: string,
     *   group: string,
     *   category: string,
     *   type: string,
     *   feature_type: string,
     *   description: string,
     *   minimum_plan: string,
     *   upgrade_message: string,
     *   limit_type: string
     * }
     */
    private static function limitFeature(string $key, string $label, string $description): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'group' => 'limits',
            'category' => 'limits',
            'type' => 'integer',
            'feature_type' => 'limit',
            'description' => $description,
            'minimum_plan' => self::PLAN_FREE,
            'upgrade_message' => 'لقد وصلت لحد باقتك الحالية. رقِّ باقتك للمتابعة بدون انقطاع.',
            'limit_type' => str_contains($key, 'monthly') || str_contains($key, 'chat_monthly') ? 'monthly' : 'lifetime',
        ];
    }

    public static function labelFor(string $key): ?string
    {
        return self::definition($key)['label'] ?? null;
    }

    public static function minimumPlanFor(string $key): string
    {
        return self::definition($key)['minimum_plan'] ?? self::PLAN_BUSINESS;
    }

    public static function upgradeMessageFor(string $key): string
    {
        return self::definition($key)['upgrade_message']
            ?? 'هذه الميزة غير متاحة في باقتك الحالية. رقِّ باقتك للمتابعة.';
    }

    public static function formatLimitLabel(string $key, mixed $value): ?string
    {
        if (! self::isIntegerKey($key)) {
            return null;
        }

        $n = max(0, (int) $value);
        $unlimited = $n === 0;

        return match ($key) {
            'branches.max' => $unlimited ? 'فروع غير محدودة' : "حتى {$n} فرع",
            'users.max' => $unlimited ? 'موظفون بلا حد' : "حتى {$n} موظف",
            'invoices.monthly.max' => $unlimited ? 'فواتير شهرية بلا حد' : "حتى {$n} فاتورة / شهر",
            'invoices.sale.max' => $unlimited ? 'فواتير بيع شهرية بلا حد' : "حتى {$n} فاتورة بيع / شهر",
            'invoices.rent.max' => $unlimited ? 'فواتير إيجار شهرية بلا حد' : "حتى {$n} فاتورة إيجار / شهر",
            'invoices.tailoring.max' => $unlimited ? 'فواتير تفصيل شهرية بلا حد' : "حتى {$n} فاتورة تفصيل / شهر",
            'ai_assistant.chat_monthly.max' => $unlimited ? 'رسائل شات شهرية بلا حد' : "حتى {$n} رسالة شات / شهر",
            'smart_assistant.messages_monthly.max' => $unlimited ? 'رسائل المساعد الذكي بلا حد' : "حتى {$n} رسالة مساعد ذكي / شهر",
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::definitions(), 'key');
    }

    public static function definition(string $key): ?array
    {
        foreach (self::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }

    public static function isBooleanKey(string $key): bool
    {
        $definition = self::definition($key);
        if ($definition !== null) {
            return ($definition['type'] ?? '') === 'boolean';
        }

        return str_ends_with($key, '.enabled') || $key === 'ai_assistant.advanced' || $key === 'smart_assistant.auto_reply';
    }

    public static function isIntegerKey(string $key): bool
    {
        $definition = self::definition($key);
        if ($definition !== null) {
            return ($definition['type'] ?? '') === 'integer';
        }

        return str_ends_with($key, '.max');
    }

    public static function normalizeValue(string $key, mixed $value): string
    {
        if (self::isBooleanKey($key)) {
            $normalized = strtolower(trim((string) $value));

            return in_array($normalized, ['1', 'true', 'yes', 'enabled', 'on'], true) ? 'true' : 'false';
        }

        $numeric = (int) $value;

        return (string) max(0, $numeric);
    }

    public static function valueType(string $key): string
    {
        return self::isBooleanKey($key) ? 'boolean' : 'integer';
    }

    public static function isEnabledValue(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'enabled'], true);
    }

    public static function invoiceLimitKeyForType(string $invoiceType): ?string
    {
        return match ($invoiceType) {
            'sell', 'sale' => 'invoices.sale.max',
            'rent' => 'invoices.rent.max',
            'tailoring' => 'invoices.tailoring.max',
            default => null,
        };
    }

    public static function moduleKeyFromFeature(string $featureKey): string
    {
        return str_replace('.enabled', '', $featureKey);
    }
}
