<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Persona;

use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use RuntimeException;

final class PersonaResolver
{
    public function __construct(
        private readonly PersonaRegistryInterface $registry,
    ) {}

    public function resolve(PromptBuildRequest $request): Persona
    {
        $persona = $this->registry->getByType($request->personaType());
        if ($persona === null) {
            throw new RuntimeException(sprintf(
                'Persona type [%s] is not registered.',
                $request->personaType()->value
            ));
        }

        return $persona;
    }
}
