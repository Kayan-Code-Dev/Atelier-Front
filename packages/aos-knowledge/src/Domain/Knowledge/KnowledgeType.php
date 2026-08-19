<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

enum KnowledgeType: string
{
    case Business = 'business';
    case Tenant = 'tenant';
    case Platform = 'platform';
    case Customer = 'customer';
    case Faq = 'faq';
    case Operational = 'operational';
    case Product = 'product';
    case Training = 'training';
    case Policy = 'policy';
    case Procedure = 'procedure';
    case Template = 'template';
    case FutureExternal = 'future_external';
}
