<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Policies;

use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class LatencyPolicy
{
    public function allows(ModelDescriptor $model, AiRequest $request): bool
    {
        return $model->typicalLatencyMs() <= $request->maxLatencyMs();
    }
}
