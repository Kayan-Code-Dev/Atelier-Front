<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Persona;

/**
 * Immutable digital-employee persona definition.
 */
final class Persona
{
    /**
     * @param  list<string>  $behaviorRules
     * @param  list<string>  $forbiddenBehaviors
     * @param  list<string>  $languagePreferences
     */
    public function __construct(
        private readonly PersonaId $id,
        private readonly PersonaType $type,
        private readonly string $name,
        private readonly string $role,
        private readonly string $tone,
        private readonly string $communicationStyle,
        private readonly array $behaviorRules,
        private readonly array $forbiddenBehaviors,
        private readonly string $escalationStyle,
        private readonly string $decisionStyle,
        private readonly array $languagePreferences,
    ) {}

    public function id(): PersonaId
    {
        return $this->id;
    }

    public function type(): PersonaType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function tone(): string
    {
        return $this->tone;
    }

    public function communicationStyle(): string
    {
        return $this->communicationStyle;
    }

    /**
     * @return list<string>
     */
    public function behaviorRules(): array
    {
        return $this->behaviorRules;
    }

    /**
     * @return list<string>
     */
    public function forbiddenBehaviors(): array
    {
        return $this->forbiddenBehaviors;
    }

    public function escalationStyle(): string
    {
        return $this->escalationStyle;
    }

    public function decisionStyle(): string
    {
        return $this->decisionStyle;
    }

    /**
     * @return list<string>
     */
    public function languagePreferences(): array
    {
        return $this->languagePreferences;
    }

    public function toPromptText(): string
    {
        $lines = [
            "You are {$this->name}, acting as {$this->role}.",
            "Tone: {$this->tone}.",
            "Communication style: {$this->communicationStyle}.",
            "Decision style: {$this->decisionStyle}.",
            "Escalation style: {$this->escalationStyle}.",
            'Behavior rules:',
        ];
        foreach ($this->behaviorRules as $rule) {
            $lines[] = '- '.$rule;
        }
        $lines[] = 'Forbidden behaviors:';
        foreach ($this->forbiddenBehaviors as $rule) {
            $lines[] = '- '.$rule;
        }
        if ($this->languagePreferences !== []) {
            $lines[] = 'Language preferences: '.implode(', ', $this->languagePreferences).'.';
        }

        return implode("\n", $lines);
    }
}
