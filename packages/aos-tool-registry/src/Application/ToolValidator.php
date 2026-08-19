<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\CapabilityRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolStatus;
use InvalidArgumentException;

final class ToolValidator
{
    public function __construct(private readonly CapabilityRegistryInterface $capabilities) {}

    /**
     * @return list<string> validation errors (empty = valid)
     */
    public function validate(ToolDescriptor $descriptor): array
    {
        $errors = [];
        $meta = $descriptor->metadata();

        if ($meta->toolName() === '') {
            $errors[] = 'toolName is required';
        }
        if ($meta->ownerDomain() === '') {
            $errors[] = 'ownerDomain is required';
        }
        if ($meta->description() === '') {
            $errors[] = 'description is required';
        }
        if ($meta->capabilities() === []) {
            $errors[] = 'at least one capability is required';
        }

        foreach ($meta->capabilities() as $capability) {
            if (! $this->capabilities->has($capability)) {
                $errors[] = 'capability not registered: '.$capability;
            }
        }

        if ($meta->status() === ToolStatus::Draft) {
            $errors[] = 'draft tools cannot be activated until published';
        }

        return $errors;
    }

    public function assertValid(ToolDescriptor $descriptor): void
    {
        $errors = $this->validate($descriptor);
        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid tool descriptor: '.implode('; ', $errors));
        }
    }
}
