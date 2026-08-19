<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Approval;

/**
 * Ordered approval chain (roles / actor ids).
 */
final class ApprovalChain
{
    /**
     * @param  list<string>  $steps
     */
    public function __construct(
        private readonly array $steps,
    ) {}

    public static function single(string $approver): self
    {
        return new self([$approver]);
    }

    public static function defaultSupervisor(): self
    {
        return new self(['human_supervisor']);
    }

    /**
     * @return list<string>
     */
    public function steps(): array
    {
        return $this->steps;
    }
}
