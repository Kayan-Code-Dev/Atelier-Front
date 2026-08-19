<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Channel;

use DressnMore\SmartAssistant\Domain\Channel\Channel;

interface ChannelInterface
{
    public function identity(): Channel;

    public function type(): string;
}
