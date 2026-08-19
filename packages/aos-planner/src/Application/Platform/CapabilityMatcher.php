<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Planner\Contracts\CapabilityMatcherInterface;
use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\CapabilityMatch;

final class CapabilityMatcher implements CapabilityMatcherInterface
{
    /**
     * @param list<string>|null $registeredCapabilities when null, required capabilities are treated as matched if non-empty
     */
    public function __construct(private readonly ?array $registeredCapabilities = null) {}

    public function match(AnalyzedIntent $intent): CapabilityMatch
    {
        $required = $intent->requiredCapabilities();
        if ($required === []) {
            return new CapabilityMatch([], [], ['no_capabilities_for_intent']);
        }

        if ($this->registeredCapabilities === null) {
            return new CapabilityMatch($required, $required, []);
        }

        $matched = [];
        $missing = [];
        foreach ($required as $capability) {
            if (in_array($capability, $this->registeredCapabilities, true) || in_array('*', $this->registeredCapabilities, true)) {
                $matched[] = $capability;
            } else {
                $missing[] = $capability;
            }
        }

        return new CapabilityMatch($required, $matched, $missing);
    }
}
