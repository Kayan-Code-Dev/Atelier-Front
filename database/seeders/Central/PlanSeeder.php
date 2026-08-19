<?php

namespace Database\Seeders\Central;

use App\Models\Central\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // الباقات الجديدة (PLG) — غير نشطة حالياً حتى الإطلاق الرسمي
            [
                'name' => 'مجانية',
                'slug' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'duration_days' => 365,
                'sort_order' => 10,
                'status' => 'inactive',
                'description' => 'ابدأ إدارة أتيلييهك — العمليات الأساسية مع حدود استخدام.',
            ],
            [
                'name' => 'البداية',
                'slug' => 'starter',
                'price' => 20.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
                'sort_order' => 11,
                'status' => 'inactive',
                'description' => 'أدر أتيلييهك باحتراف — المساعد الذكي والموقع وفواتير بلا حد.',
            ],
            [
                'name' => 'الاحترافية',
                'slug' => 'professional',
                'price' => 40.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
                'sort_order' => 12,
                'status' => 'inactive',
                'description' => 'نمِّ وحسّن عملك — المستشار الذكي والتحليلات وتعدد الفروع والمحاسبة.',
            ],
            [
                'name' => 'الأعمال',
                'slug' => 'business',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
                'sort_order' => 13,
                'status' => 'inactive',
                'description' => 'تسعير مخصص — توسّع بحدود مؤسساتية ودعم مخصص.',
            ],

            // الباقات الحالية النشطة
            [
                'name' => 'أساسية',
                'slug' => 'basic',
                'price' => 49.00,
                'currency' => 'EGP',
                'billing_cycle' => 'monthly',
                'duration_days' => 365,
                'sort_order' => 1,
                'status' => 'active',
                'description' => 'باقة للشركات الصغيرة والأتيليهات الناشئة.',
            ],
            [
                'name' => 'احترافية',
                'slug' => 'pro',
                'price' => 99.00,
                'currency' => 'EGP',
                'billing_cycle' => 'monthly',
                'duration_days' => 365,
                'sort_order' => 2,
                'status' => 'active',
                'description' => 'باقة للأتيليهات النامية مع تقارير ومساعد ذكي.',
            ],
            [
                'name' => 'مؤسسات',
                'slug' => 'enterprise',
                'price' => 199.00,
                'currency' => 'EGP',
                'billing_cycle' => 'monthly',
                'duration_days' => 365,
                'sort_order' => 3,
                'status' => 'active',
                'description' => 'باقة متقدمة للأتيليهات متعددة الفروع والمحاسبة.',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
