<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Conversation;

enum OperatingMode: string
{
    case Assistant = 'assistant';
    case Hybrid = 'hybrid';
    case FullAuto = 'full_auto';
}
