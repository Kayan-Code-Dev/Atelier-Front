<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Capability;

use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class CapabilityRegistry
{
    /**
     * Resolve required capabilities from request (+ streaming flag).
     *
     * @return list<ModelCapability>
     */
    public function resolveRequired(AiRequest $request): array
    {
        $caps = $request->requiredCapabilities();
        if ($request->streaming() && ! in_array(ModelCapability::Streaming, $caps, true)) {
            $caps[] = ModelCapability::Streaming;
        }

        return array_values($caps);
    }
}
