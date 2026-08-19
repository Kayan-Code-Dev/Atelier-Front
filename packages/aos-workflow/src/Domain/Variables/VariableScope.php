<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Variables;

enum VariableScope: string
{
    case Tenant = 'tenant_variables';
    case Conversation = 'conversation_variables';
    case Customer = 'customer_variables';
    case Runtime = 'runtime_variables';
    case System = 'system_variables';
}
