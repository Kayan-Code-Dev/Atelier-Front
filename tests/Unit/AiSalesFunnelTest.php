<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AiSalesFunnel;
use PHPUnit\Framework\TestCase;

final class AiSalesFunnelTest extends TestCase
{
    public function test_builds_conversion_and_dropoff_between_stages(): void
    {
        $stages = AiSalesFunnel::build([
            'conversations' => 100,
            'leads' => 40,
            'qualified' => 20,
            'interested' => 10,
            'demo_trial' => 5,
            'paid' => 2,
        ]);

        $this->assertSame(6, count($stages));
        $this->assertNull($stages[0]['conversion_from_previous']);
        $this->assertSame(40.0, $stages[1]['conversion_from_previous']);
        $this->assertSame(60.0, $stages[1]['dropoff_from_previous']);
        $this->assertSame(2.0, AiSalesFunnel::conversionRate(2, 100));
        $this->assertSame(0.0, AiSalesFunnel::conversionRate(2, 0));
    }
}
