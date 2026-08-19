<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Policy;

use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;

final class PolicyEvaluationResult
{
    /**
     * @param  list<PolicyDefinition>  $matched
     */
    public function __construct(
        private readonly ?AuthorizationOutcome $dominantEffect,
        private readonly array $matched,
        private readonly string $reason = '',
    ) {}

    public static function none(): self
    {
        return new self(null, [], 'no policies matched');
    }

    /**
     * @param  list<PolicyDefinition>  $matched
     */
    public static function of(?AuthorizationOutcome $effect, array $matched, string $reason = ''): self
    {
        return new self($effect, $matched, $reason);
    }

    public function dominantEffect(): ?AuthorizationOutcome
    {
        return $this->dominantEffect;
    }

    /**
     * @return list<PolicyDefinition>
     */
    public function matched(): array
    {
        return $this->matched;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
