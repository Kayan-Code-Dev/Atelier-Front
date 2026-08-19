<?php

namespace Tests\Unit;

use App\Support\PlanFeatureCatalog;
use Tests\TestCase;

class SmartAssistantPlanQuotaCatalogTest extends TestCase
{
    public function test_catalog_includes_assistant_message_quota(): void
    {
        $this->assertContains('smart_assistant.messages_monthly.max', PlanFeatureCatalog::keys());
        $this->assertTrue(PlanFeatureCatalog::isIntegerKey('smart_assistant.messages_monthly.max'));

        $matrix = PlanFeatureCatalog::defaultMatrix();
        $this->assertSame(50, $matrix[PlanFeatureCatalog::PLAN_FREE]['smart_assistant.messages_monthly.max']);
        $this->assertSame(300, $matrix[PlanFeatureCatalog::PLAN_STARTER]['smart_assistant.messages_monthly.max']);
        $this->assertSame(1500, $matrix[PlanFeatureCatalog::PLAN_PROFESSIONAL]['smart_assistant.messages_monthly.max']);
        $this->assertSame(0, $matrix[PlanFeatureCatalog::PLAN_BUSINESS]['smart_assistant.messages_monthly.max']);
        $this->assertTrue((bool) $matrix[PlanFeatureCatalog::PLAN_STARTER]['smart_assistant.enabled']);
        $this->assertTrue((bool) $matrix[PlanFeatureCatalog::PLAN_STARTER]['smart_assistant.auto_reply']);
    }

    public function test_format_limit_label_for_assistant_messages(): void
    {
        $this->assertSame('حتى 300 رسالة مساعد ذكي / شهر', PlanFeatureCatalog::formatLimitLabel('smart_assistant.messages_monthly.max', 300));
        $this->assertSame('رسائل المساعد الذكي بلا حد', PlanFeatureCatalog::formatLimitLabel('smart_assistant.messages_monthly.max', 0));
    }
}
