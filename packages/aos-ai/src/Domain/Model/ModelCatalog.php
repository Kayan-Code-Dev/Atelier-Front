<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Model;

final class ModelCatalog
{
    public function __construct(
        private readonly ModelRegistryInterface $registry,
    ) {}

    /** @return list<ModelDescriptor> */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->registry->all(),
            static fn (ModelDescriptor $m): bool => $m->isEnabled()
        ));
    }

    public function find(ModelId $id): ?ModelDescriptor
    {
        return $this->registry->get($id);
    }
}
