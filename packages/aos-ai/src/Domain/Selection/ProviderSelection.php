<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Selection;

use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;

final class ProviderSelection
{
    public function __construct(
        private readonly ProviderDescriptor $provider,
        private readonly ModelDescriptor $model,
        private readonly float $score,
        private readonly string $reason = '',
    ) {}

    public function provider(): ProviderDescriptor
    {
        return $this->provider;
    }

    public function model(): ModelDescriptor
    {
        return $this->model;
    }

    public function score(): float
    {
        return $this->score;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
