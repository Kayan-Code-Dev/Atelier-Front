<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Support\AiSales\AiSalesIntent;

/**
 * Decides whether a discovery slot may be asked this turn.
 * Missing memory is not sufficient reason to ask.
 */
final class DressnMoreQualificationGate
{
    /**
     * @return list<string>
     */
    public function branchQuestionPatterns(): array
    {
        return [
            'كام فرع',
            'كم فرع',
            'عدد الفروع',
            'عندك كام فرع',
            'عندك كم فرع',
            'كام فرع عندك',
            'كم فرع عندك',
            'الأتيليه فيه كام فرع',
            'الاتيليه فيه كام فرع',
            'حضرتك عندك كام فرع',
            'how many branches',
            'number of branches',
            'how many locations',
        ];
    }

    public function isBranchQuestion(string $text): bool
    {
        return $this->matchesSlot($text, 'branches');
    }

    public function matchesSlot(string $text, string $slot): bool
    {
        $hay = mb_strtolower(trim($text));
        if ($hay === '') {
            return false;
        }

        $patterns = match ($slot) {
            'branches' => $this->branchQuestionPatterns(),
            'users' => ['كام شخص', 'كم شخص', 'كام موظف', 'كم موظف', 'how many people', 'how many users'],
            'workflow' => ['بتديروا الحجوزات', 'how do you currently manage'],
            'pain' => ['أكتر حاجة متعبة', 'most tiring'],
            'requirements' => ['أهم حاجة بتدور', 'most important thing you need'],
            'name' => ['أحب أنادي حضرتك بإيه', 'اسم حضرتك', 'what should i call you', 'what should I call you'],
            default => [],
        };

        foreach ($patterns as $pattern) {
            if ($pattern !== '' && mb_strpos($hay, mb_strtolower($pattern)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<string>
     */
    public function askedSlotsFromHistory(array $messages): array
    {
        $asked = [];
        foreach ($messages as $row) {
            $author = strtolower((string) ($row['author'] ?? ''));
            if (! in_array($author, ['ai', 'ai_agent', 'assistant', 'bot'], true)) {
                continue;
            }
            $body = (string) ($row['body'] ?? '');
            foreach (['branches', 'users', 'workflow', 'pain', 'requirements', 'name'] as $slot) {
                if ($this->matchesSlot($body, $slot)) {
                    $asked[] = $slot;
                }
            }
        }

        return array_values(array_unique($asked));
    }

    /**
     * Slots that are actually required for the current business action.
     *
     * @return list<string>
     */
    public function requiredSlots(?AiSalesIntent $intent, bool $needsPlanFit, string $nextAction): array
    {
        if ($needsPlanFit || $nextAction === 'qualify_plan' || $intent === AiSalesIntent::PlanRecommendation) {
            return ['branches', 'users'];
        }

        return [];
    }

    public function mayAskSlot(
        string $slot,
        bool $known,
        bool $alreadyAsked,
        bool $requiredForAction,
        bool $relevantToIntent,
        bool $newRelevantContext,
    ): bool {
        if (! $relevantToIntent || ! $requiredForAction) {
            return false;
        }
        if ($known) {
            return false;
        }
        if ($alreadyAsked && ! $newRelevantContext) {
            return false;
        }

        return true;
    }

    public function mayAttachQuestion(string $mode): bool
    {
        return in_array($mode, ['qualify_plan', 'discovery'], true);
    }

    public function shouldAbandonPending(?AiSalesIntent $intent, string $last): bool
    {
        if ($intent !== null && $intent !== AiSalesIntent::PlanRecommendation) {
            return true;
        }

        $hay = mb_strtolower(trim($last));

        return $hay === '' || $this->has($hay, ['هاي', 'hello', 'hi', 'hey', 'تمام', 'ماشي', 'ok']);
    }

    public function hasNewBranchContext(string $text): bool
    {
        $hay = mb_strtolower(trim($text));
        if ($hay === '') {
            return false;
        }

        return $this->has($hay, [
            'هفتح فرع',
            'فتح فرع',
            'تعدد فروع',
            'multi-branch',
            'another branch',
            'new branch',
            'فرع جديد',
        ]);
    }

    /**
     * @param  list<string>  $needles
     */
    private function has(string $hay, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && mb_strpos($hay, mb_strtolower($needle)) !== false) {
                return true;
            }
        }

        return false;
    }
}
