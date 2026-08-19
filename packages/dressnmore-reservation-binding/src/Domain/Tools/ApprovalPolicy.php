<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Tools;

enum ApprovalPolicy: string
{
    case None = 'none';
    case Often = 'often';
    case Always = 'always';
}
