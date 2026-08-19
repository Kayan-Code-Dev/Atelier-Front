<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Mode;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationRequest;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;

/**
 * Resolves and constrains operating modes for authorization.
 */
final class OperatingModeManager
{
    /** @var array<string, true> */
    private array $enabled = [];

    public function __construct()
    {
        foreach (OperatingMode::cases() as $mode) {
            $this->enabled[$mode->value] = true;
        }
    }

    public function enable(OperatingModeCode $mode): void
    {
        $this->enabled[$mode->toString()] = true;
    }

    public function disable(OperatingModeCode $mode): void
    {
        unset($this->enabled[$mode->toString()]);
    }

    public function isEnabled(OperatingModeCode $mode): bool
    {
        return isset($this->enabled[$mode->toString()]);
    }

    public function resolve(AuthorizationRequest $request): OperatingModeCode
    {
        $mode = $request->operatingMode();
        if (! $this->isEnabled($mode)) {
            return OperatingModeCode::fromEnum(OperatingMode::Maintenance);
        }

        return $mode;
    }

    public function hardDenyOutcome(OperatingModeCode $mode): ?AuthorizationOutcome
    {
        $builtin = $mode->toBuiltin();

        return match ($builtin) {
            OperatingMode::HumanOnly => AuthorizationOutcome::HumanEscalation,
            OperatingMode::Maintenance => AuthorizationOutcome::RetryLater,
            OperatingMode::ReadOnly => null, // capability-specific later
            default => null,
        };
    }

    public function allowsMutations(OperatingModeCode $mode): bool
    {
        $builtin = $mode->toBuiltin();

        return ! in_array($builtin, [
            OperatingMode::ReadOnly,
            OperatingMode::HumanOnly,
            OperatingMode::Maintenance,
        ], true);
    }
}
