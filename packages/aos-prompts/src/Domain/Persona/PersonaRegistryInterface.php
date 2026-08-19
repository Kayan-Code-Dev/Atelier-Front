<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Persona;

interface PersonaRegistryInterface
{
    public function register(Persona $persona): void;

    public function get(PersonaId $id): ?Persona;

    public function getByType(PersonaType $type): ?Persona;

    /**
     * @return list<Persona>
     */
    public function all(): array;
}
