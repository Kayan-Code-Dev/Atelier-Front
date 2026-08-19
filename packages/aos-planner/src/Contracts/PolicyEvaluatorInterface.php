<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Contracts;

use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\PolicyEvaluation;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;

interface PolicyEvaluatorInterface
{
    public function evaluate(AnalyzedIntent $intent, ToolSelection $selection): PolicyEvaluation;
}
