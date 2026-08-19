<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Template;

/**
 * Thin template engine over registered PromptTemplate bodies.
 */
final class PromptTemplateEngine
{
    public function __construct(
        private readonly PromptTemplateRegistryInterface $registry,
    ) {}

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function render(PromptTemplateType $type, array $variables = []): string
    {
        $template = $this->registry->getByType($type);
        if ($template === null) {
            return '';
        }

        return $template->render($variables);
    }

    public function versionOf(PromptTemplateType $type): ?string
    {
        return $this->registry->getByType($type)?->version();
    }
}
