<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Tools;

use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;

/**
 * Immutable Business Tool contract for Reservation Domain binding.
 */
final class ReservationToolContract
{
    /**
     * @param list<string> $inputs
     * @param list<string> $outputs
     * @param list<string> $capabilities
     * @param list<string> $expectedEvents
     */
    public function __construct(
        private readonly ReservationToolName $name,
        private readonly string $purpose,
        private readonly array $inputs,
        private readonly array $outputs,
        private readonly array $capabilities,
        private readonly string $permission,
        private readonly ToolRiskLevel $riskLevel,
        private readonly ApprovalPolicy $approvalPolicy,
        private readonly array $expectedEvents,
    ) {}

    public function name(): ReservationToolName { return $this->name; }
    public function purpose(): string { return $this->purpose; }
    /** @return list<string> */
    public function inputs(): array { return $this->inputs; }
    /** @return list<string> */
    public function outputs(): array { return $this->outputs; }
    /** @return list<string> */
    public function capabilities(): array { return $this->capabilities; }
    public function requiredCapability(): string { return $this->capabilities[0] ?? ''; }
    public function permission(): string { return $this->permission; }
    public function riskLevel(): ToolRiskLevel { return $this->riskLevel; }
    public function approvalPolicy(): ApprovalPolicy { return $this->approvalPolicy; }
    /** @return list<string> */
    public function expectedEvents(): array { return $this->expectedEvents; }
}
