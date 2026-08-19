<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Persona;

final class PersonaRegistry implements PersonaRegistryInterface
{
    /** @var array<string, Persona> */
    private array $byId = [];

    /** @var array<string, string> */
    private array $byType = [];

    public function register(Persona $persona): void
    {
        $this->byId[$persona->id()->toString()] = $persona;
        $this->byType[$persona->type()->value] = $persona->id()->toString();
    }

    public function get(PersonaId $id): ?Persona
    {
        return $this->byId[$id->toString()] ?? null;
    }

    public function getByType(PersonaType $type): ?Persona
    {
        $id = $this->byType[$type->value] ?? null;
        if ($id === null) {
            return null;
        }

        return $this->byId[$id] ?? null;
    }

    public function all(): array
    {
        return array_values($this->byId);
    }
}
