<?php

declare(strict_types=1);

namespace DressnMore\Aos\Observability\Logging;

use DressnMore\Aos\Observability\Contracts\LoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Stringable;

/**
 * Bridges AOS logger contract to the application PSR logger.
 */
final class PsrLoggerAdapter implements LoggerInterface
{
    public function __construct(
        private readonly PsrLoggerInterface $logger,
    ) {}

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $this->withAosContext($context));
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $this->withAosContext($context));
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $this->withAosContext($context));
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $this->withAosContext($context));
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $this->withAosContext($context));
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $this->withAosContext($context));
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $this->withAosContext($context));
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $this->withAosContext($context));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function withAosContext(array $context): array
    {
        $context['aos'] = true;

        return $context;
    }
}
