<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Integration;

use DressnMore\SmartAssistant\Domain\Integration\Integration;

interface IntegrationInterface
{
    public function identity(): Integration;
}
