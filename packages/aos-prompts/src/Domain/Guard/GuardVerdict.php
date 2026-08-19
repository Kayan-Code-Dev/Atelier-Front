<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Guard;

enum GuardVerdict: string
{
    case Allow = 'allow';
    case Sanitize = 'sanitize';
    case Reject = 'reject';
}
