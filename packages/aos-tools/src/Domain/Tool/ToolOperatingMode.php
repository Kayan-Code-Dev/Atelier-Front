<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

/**
 * Operating modes a tool may be invoked under (opaque to Permission Engine).
 */
enum ToolOperatingMode: string
{
    case Assistant = 'assistant';
    case Hybrid = 'hybrid';
    case FullAuto = 'full_auto';
}
