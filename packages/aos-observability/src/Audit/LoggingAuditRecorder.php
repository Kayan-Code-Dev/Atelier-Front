<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Audit;

use DressnMore\Aos\Observability\Contracts\AuditRecorderInterface;
use DressnMore\Aos\Observability\Contracts\LoggerInterface;

/**
 * Foundation audit recorder that writes structured audit lines to the logger.
 * Persistence backends belong to later sprints.
 */
final class LoggingAuditRecorder implements AuditRecorderInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function record(string $action, array $context = []): void
    {
        $this->logger->info('aos.audit.'.$action, $context);
    }
}
