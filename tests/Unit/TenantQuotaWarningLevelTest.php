<?php

namespace Tests\Unit;

use App\Services\Tenant\TenantQuotaService;
use ReflectionMethod;
use Tests\TestCase;

class TenantQuotaWarningLevelTest extends TestCase
{
    public function test_free_invoice_progressive_thresholds(): void
    {
        $service = app(TenantQuotaService::class);
        $method = new ReflectionMethod(TenantQuotaService::class, 'warningLevel');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($service, 9, 15));
        $this->assertSame('info', $method->invoke($service, 10, 15));
        $this->assertSame('info', $method->invoke($service, 11, 15));
        $this->assertSame('warning', $method->invoke($service, 12, 15));
        $this->assertSame('warning', $method->invoke($service, 13, 15));
        $this->assertSame('urgent', $method->invoke($service, 14, 15));
        $this->assertSame('exhausted', $method->invoke($service, 15, 15));
        $this->assertSame('exhausted', $method->invoke($service, 16, 15));
    }
}
