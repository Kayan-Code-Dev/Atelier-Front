<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Contracts;

use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

interface ToolValidatorInterface
{
    /**
     * @return list<\DressnMore\Aos\Tools\Domain\Result\ToolFailure>
     */
    public function validate(ToolRequest $request, ToolManifest $manifest): array;
}
