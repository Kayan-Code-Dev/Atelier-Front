<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Clarification;

use DressnMore\Aos\Planner\Domain\Intent\IntentKind;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolution;

final class ClarificationEngine
{
    public function requiresClarification(IntentResolution $resolution): bool
    {
        return in_array($resolution->kind(), [
            IntentKind::Ambiguous,
            IntentKind::Conflicting,
            IntentKind::Unknown,
        ], true);
    }

    public function promptFor(IntentResolution $resolution): string
    {
        return match ($resolution->kind()) {
            IntentKind::Unknown => 'لم أفهم طلبك بوضوح. هل يمكنك إعادة صياغته؟',
            IntentKind::Ambiguous => 'طلبك يحتمل أكثر من معنى. هل تقصد الاستفسار أم تنفيذ إجراء؟',
            IntentKind::Conflicting => 'هناك تعارض في الطلب (مثل الحجز والإلغاء معاً). أيهما تريد؟',
            default => '',
        };
    }
}
