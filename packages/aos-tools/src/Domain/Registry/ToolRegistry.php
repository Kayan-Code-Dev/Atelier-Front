<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Registry;

use DressnMore\Aos\Tools\Domain\Contracts\BusinessToolHandlerInterface;
use DressnMore\Aos\Tools\Domain\Exceptions\ToolAlreadyRegisteredException;
use DressnMore\Aos\Tools\Domain\Exceptions\ToolNotFoundException;
use DressnMore\Aos\Tools\Domain\Tool\ToolCategoryCode;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

/**
 * In-process registry keyed by ToolIdentifier (never by PHP class name).
 */
final class ToolRegistry implements ToolRegistryInterface
{
    /** @var array<string, BusinessToolHandlerInterface> */
    private array $handlers = [];

    public function register(BusinessToolHandlerInterface $handler): void
    {
        $key = $handler->identifier()->toString();
        if (isset($this->handlers[$key])) {
            throw ToolAlreadyRegisteredException::for($handler->identifier());
        }

        $this->handlers[$key] = $handler;
    }

    public function unregister(ToolIdentifier $identifier): void
    {
        unset($this->handlers[$identifier->toString()]);
    }

    public function has(ToolIdentifier $identifier): bool
    {
        return isset($this->handlers[$identifier->toString()]);
    }

    public function get(ToolIdentifier $identifier): ?BusinessToolHandlerInterface
    {
        return $this->handlers[$identifier->toString()] ?? null;
    }

    public function getManifest(ToolIdentifier $identifier): ?ToolManifest
    {
        return $this->get($identifier)?->manifest();
    }

    public function require(ToolIdentifier $identifier): BusinessToolHandlerInterface
    {
        $handler = $this->get($identifier);
        if ($handler === null) {
            throw ToolNotFoundException::for($identifier);
        }

        return $handler;
    }

    /**
     * @return list<ToolManifest>
     */
    public function allManifests(): array
    {
        $manifests = [];
        foreach ($this->handlers as $handler) {
            $manifests[] = $handler->manifest();
        }

        return $manifests;
    }

    /**
     * @return list<ToolManifest>
     */
    public function discoverByCategory(ToolCategoryCode $category): array
    {
        return array_values(array_filter(
            $this->allManifests(),
            static fn (ToolManifest $m): bool => $m->category()->equals($category)
        ));
    }

    /**
     * @return list<ToolManifest>
     */
    public function discoverByCapability(string $capability): array
    {
        return array_values(array_filter(
            $this->allManifests(),
            static fn (ToolManifest $m): bool => in_array($capability, $m->capabilities(), true)
        ));
    }

    public function count(): int
    {
        return count($this->handlers);
    }
}
