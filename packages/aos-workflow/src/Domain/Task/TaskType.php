<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Task;

enum TaskType: string
{
    case AiTask = 'ai_task';
    case BusinessToolTask = 'business_tool_task';
    case HumanTask = 'human_task';
    case ApprovalTask = 'approval_task';
    case NotificationTask = 'notification_task';
    case DelayTask = 'delay_task';
    case DecisionTask = 'decision_task';
    case ConditionTask = 'condition_task';
    case ParallelTask = 'parallel_task';
    case SequentialTask = 'sequential_task';
}
