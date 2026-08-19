<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Policy;

final class PolicyDescriptor
{
    /**
     * @param list<string> $rules
     */
    public function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly array $rules = [],
        private readonly string $ownerDomain = 'platform',
    ) {}

    public function name(): string { return $this->name; }
    public function description(): string { return $this->description; }
    /** @return list<string> */
    public function rules(): array { return $this->rules; }
    public function ownerDomain(): string { return $this->ownerDomain; }
}
