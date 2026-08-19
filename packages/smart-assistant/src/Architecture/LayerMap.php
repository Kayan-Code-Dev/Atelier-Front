<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Architecture;

/**
 * Layered architecture map (no infrastructure adapters in Sprint 21).
 */
final class LayerMap
{
    public const DOMAIN = 'domain';
    public const APPLICATION = 'application';
    public const CONTRACTS = 'contracts';
    public const REGISTRY = 'registry';
    public const INFRASTRUCTURE = 'infrastructure'; // future
    public const PRESENTATION = 'presentation'; // Sprint 22+

    /**
     * Allowed dependency direction (outer may depend on inner only).
     *
     * @return list<string>
     */
    public static function inwardOrder(): array
    {
        return [
            self::PRESENTATION,
            self::INFRASTRUCTURE,
            self::APPLICATION,
            self::REGISTRY,
            self::CONTRACTS,
            self::DOMAIN,
        ];
    }
}
