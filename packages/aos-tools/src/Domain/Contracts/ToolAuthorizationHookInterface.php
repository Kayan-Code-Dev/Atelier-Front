<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Contracts;

use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

/**
 * Authorization hook — Permission Engine plugs in later; Sprint 4 ships a contract + default allow/deny adapters.
 */
interface ToolAuthorizationHookInterface
{
    public function authorize(ToolRequest $request, ToolManifest $manifest): AuthorizationDecision;
}
