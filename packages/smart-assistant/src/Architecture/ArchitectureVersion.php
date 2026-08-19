<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Architecture;

/**
 * Frozen architecture marker — Smart Assistant v1.0.0.
 *
 * Changes after freeze: bugfixes and contract-compatible extensions only.
 */
final class ArchitectureVersion
{
    public const MAJOR = 1;
    public const MINOR = 0;
    public const PATCH = 0;
    public const STATUS = 'frozen';

    public const MODULE = 'smart.assistant';
    public const PACKAGE = 'dressnmore/smart-assistant';

    /** Compatibility matrix with AI Core packages (semantic ranges). */
    public const COMPAT = [
        'dressnmore/aos-core' => '^0.1.0',
        'dressnmore/aos-planner' => '^0.18.0',
        'dressnmore/aos-tools' => '^0.4.0',
        'dressnmore/aos-tool-registry' => '^0.16.0',
        'dressnmore/aos-response' => '^0.20.0',
        'dressnmore/aos-tenant-ai' => '^0.17.0',
    ];

    public static function semver(): string
    {
        return self::MAJOR.'.'.self::MINOR.'.'.self::PATCH;
    }

    public static function isFrozen(): bool
    {
        return self::STATUS === 'frozen';
    }
}
