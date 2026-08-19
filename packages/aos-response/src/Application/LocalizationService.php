<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Response\Contracts\LocalizationInterface;
use DressnMore\Aos\Response\Domain\Localization\MessageCatalog;

final class LocalizationService implements LocalizationInterface
{
    public function __construct(
        private readonly string $locale = 'ar',
        private readonly MessageCatalog $catalog = new MessageCatalog(),
    ) {}

    public function locale(): string
    {
        return $this->locale;
    }

    public function withLocale(string $locale): self
    {
        $normalized = strtolower(substr($locale, 0, 2));
        if (! in_array($normalized, ['ar', 'en'], true)) {
            $normalized = 'ar';
        }

        return new self($normalized, $this->catalog);
    }

    public function translate(string $key, array $replacements = [], ?string $locale = null): string
    {
        $loc = $locale !== null ? strtolower(substr($locale, 0, 2)) : $this->locale;
        $all = $this->catalog->all();
        $dict = $all[$loc] ?? $all['ar'];
        $template = $dict[$key] ?? ($all['en'][$key] ?? $key);

        foreach ($replacements as $name => $value) {
            $template = str_replace(':'.$name, (string) $value, $template);
        }

        return $template;
    }
}
