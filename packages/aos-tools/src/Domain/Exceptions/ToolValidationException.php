<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Exceptions;

final class ToolValidationException extends ToolException
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        private readonly array $messages,
    ) {
        parent::__construct('Tool validation failed: '.implode('; ', $messages));
    }

    /**
     * @return list<string>
     */
    public function messages(): array
    {
        return $this->messages;
    }
}
