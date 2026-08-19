<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Registry\PromptRegistryInterface;
use DressnMore\SmartAssistant\Domain\Knowledge\Prompt;

final class InMemoryPromptRegistry implements PromptRegistryInterface
{
    /** @var array<string, Prompt> */
    private array $items = [];

    public function register(Prompt $prompt): void
    {
        $this->items[$prompt->key().'@'.$prompt->locale()] = $prompt;
    }

    public function get(string $key, string $locale = 'ar'): ?Prompt
    {
        return $this->items[$key.'@'.$locale] ?? null;
    }
}
