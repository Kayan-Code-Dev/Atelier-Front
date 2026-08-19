<?php

declare(strict_types=1);

namespace Tests\Unit;

use DressnMore\SmartAssistantProduct\Domain\WhatsAppSessionKey;
use PHPUnit\Framework\TestCase;

final class WhatsAppSessionKeyTest extends TestCase
{
    public function test_legacy_and_connection_keys(): void
    {
        $this->assertSame('12', WhatsAppSessionKey::legacy(12));
        $this->assertSame('12c45', WhatsAppSessionKey::forConnection(12, 45));
    }

    public function test_parse_legacy_and_composite(): void
    {
        $legacy = WhatsAppSessionKey::parse('12');
        $this->assertSame(12, $legacy['tenant_id']);
        $this->assertNull($legacy['connection_id']);

        $composite = WhatsAppSessionKey::parse('12c45');
        $this->assertSame(12, $composite['tenant_id']);
        $this->assertSame(45, $composite['connection_id']);
    }
}
