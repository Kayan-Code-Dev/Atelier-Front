<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Policies;

use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class SecurityPolicy
{
    /** @var list<string> */
    private const BLOCKED_KINDS_FOR_STRICT = [];

    public function allows(ProviderDescriptor $provider, AiRequest $request): bool
    {
        $strict = (bool) ($request->metadata()['strict_security'] ?? false);
        if (! $strict) {
            return true;
        }

        return ! in_array($provider->kind()->value, self::BLOCKED_KINDS_FOR_STRICT, true);
    }
}
