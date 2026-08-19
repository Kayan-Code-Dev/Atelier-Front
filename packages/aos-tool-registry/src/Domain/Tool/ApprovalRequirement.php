<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Tool;

enum ApprovalRequirement: string
{
    case None = 'none';
    case Often = 'often';
    case Always = 'always';
}
