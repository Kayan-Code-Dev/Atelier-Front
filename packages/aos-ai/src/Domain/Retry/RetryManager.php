<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Retry;

final class RetryManager
{
    public function __construct(
        private readonly int $maxAttempts = 2,
    ) {}

    /**
     * @template T
     * @param  callable(): T  $operation
     * @return T
     */
    public function run(callable $operation): mixed
    {
        $attempt = 0;
        $last = null;
        while ($attempt < $this->maxAttempts) {
            $attempt++;
            try {
                return $operation();
            } catch (\Throwable $e) {
                $last = $e;
            }
        }

        throw $last ?? new \RuntimeException('Retry exhausted.');
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }
}
