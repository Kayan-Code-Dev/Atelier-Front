<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Permission;

/**
 * Resolved permission keys for this context cycle (opaque strings — no Permission Engine).
 */
final class ResolvedPermissions
{
    /**
     * @param  list<string>  $keys
     */
    public function __construct(
        private readonly array $keys,
    ) {}

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param  list<string>  $keys
     */
    public static function of(array $keys): self
    {
        return new self(array_values(array_unique($keys)));
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return $this->keys;
    }

    public function allows(string $key): bool
    {
        return in_array($key, $this->keys, true);
    }
}
