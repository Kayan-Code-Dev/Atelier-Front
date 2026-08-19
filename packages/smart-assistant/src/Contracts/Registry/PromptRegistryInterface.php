<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Registry;

use DressnMore\SmartAssistant\Domain\Knowledge\Prompt;

interface PromptRegistryInterface
{
    public function register(Prompt $prompt): void;

    public function get(string $key, string $locale = 'ar'): ?Prompt;
}
