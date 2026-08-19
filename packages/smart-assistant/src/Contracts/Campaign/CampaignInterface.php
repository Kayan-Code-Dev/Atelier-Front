<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Campaign;

use DressnMore\SmartAssistant\Domain\Campaign\Campaign;

interface CampaignInterface
{
    public function identity(): Campaign;
}
