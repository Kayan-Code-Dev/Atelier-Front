<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Summary;

enum SummaryKind: string
{
    case Incremental = 'incremental';
    case Rolling = 'rolling';
    case Final = 'final';
}
