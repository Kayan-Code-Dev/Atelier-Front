<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Campaign;

use DressnMore\SmartAssistant\Domain\Campaign\Campaign;

interface CampaignManagerInterface
{
    public function create(string $tenantId, string $name, ?string $channelId = null): Campaign;
}
