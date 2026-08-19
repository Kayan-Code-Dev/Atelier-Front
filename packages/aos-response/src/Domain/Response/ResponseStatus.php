<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Response;

enum ResponseStatus: string
{
    case Success = 'success';
    case PartialSuccess = 'partial_success';
    case Failed = 'failed';
    case Empty = 'empty';
}
