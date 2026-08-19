<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Infrastructure\Bootstrap;

use DressnMore\Aos\Communication\Domain\Channel\ChannelAccount;
use DressnMore\Aos\Communication\Domain\Channel\ChannelRegistryInterface;
use DressnMore\Aos\Communication\Domain\Channel\ChannelType;
use DressnMore\Aos\Communication\Domain\Channel\VerificationStatus;
use DressnMore\Aos\Communication\Infrastructure\InMemory\StubChannelAdapter;

final class BuiltinChannelCatalogBootstrap
{
    public function __construct(private readonly ChannelRegistryInterface $registry) {}

    public function seed(): void
    {
        foreach ([ChannelType::WebChat, ChannelType::Email, ChannelType::Telegram] as $type) {
            $this->registry->register(
                new ChannelAccount($type, 'default-'.$type->value, VerificationStatus::Verified),
                new StubChannelAdapter($type),
            );
        }
    }
}
