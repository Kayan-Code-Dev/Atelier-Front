<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Workflow;

enum WorkflowLifecycleStatus: string
{
    case Draft = 'draft';
    case Testing = 'testing';
    case Published = 'published';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Archived = 'archived';
}
