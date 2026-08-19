<?php

declare(strict_types=1);

namespace App\Support\AiSales;

enum AiSalesConfidence: string
{
    case High = 'HIGH_CONFIDENCE';
    case Medium = 'MEDIUM_CONFIDENCE';
    case Low = 'LOW_CONFIDENCE';
}
