<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Module;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationRepositoryInterface;
use DressnMore\Aos\Core\Module\AbstractModule;

/**
 * AOS Conversation Engine module (Sprint 2).
 */
final class ConversationModule extends AbstractModule
{
    public function __construct(
        private readonly ConversationRepositoryInterface $repository,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.conversation');
    }

    public function title(): string
    {
        return 'AOS Conversation Engine';
    }

    public function version(): string
    {
        return '0.2.0';
    }

    public function isHealthy(): bool
    {
        return $this->repository instanceof ConversationRepositoryInterface;
    }
}
