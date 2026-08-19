<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Cache;

use DressnMore\Aos\Context\Domain\Snapshot\ContextSnapshot;
use DressnMore\Aos\Context\Domain\Snapshot\ContextSnapshotId;

/**
 * Cache port for Context Snapshots (no concrete store in Sprint 3 beyond in-memory).
 */
interface ContextCacheInterface
{
    public function put(ContextSnapshot $snapshot): void;

    public function get(ContextSnapshotId $id): ?ContextSnapshot;

    public function forget(ContextSnapshotId $id): void;

    public function forgetExpired(): int;
}
