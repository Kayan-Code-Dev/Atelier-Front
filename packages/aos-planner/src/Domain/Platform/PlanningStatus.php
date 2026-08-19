<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

enum PlanningStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case RequiresApproval = 'requires_approval';
}
