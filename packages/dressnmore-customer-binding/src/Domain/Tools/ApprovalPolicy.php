<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Tools;

enum ApprovalPolicy: string
{
    case None = 'none';
    case Optional = 'optional';
    case Often = 'often';
    case Always = 'always';
}
