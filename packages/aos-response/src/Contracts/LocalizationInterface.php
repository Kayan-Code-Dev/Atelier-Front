<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Contracts;

interface LocalizationInterface
{
    public function locale(): string;

    /**
     * @param array<string, scalar|null> $replacements
     */
    public function translate(string $key, array $replacements = [], ?string $locale = null): string;

    public function withLocale(string $locale): self;
}
