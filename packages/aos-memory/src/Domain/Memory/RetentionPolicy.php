<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

enum RetentionPolicy: string
{
    case Ephemeral = 'ephemeral';
    case ConversationScoped = 'conversation_scoped';
    case CustomerScoped = 'customer_scoped';
    case TenantScoped = 'tenant_scoped';
    case Durable = 'durable';
}
