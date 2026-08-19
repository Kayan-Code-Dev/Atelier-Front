<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Intent;

use DressnMore\Aos\Planner\Domain\Context\PlanningContext;

/**
 * Rule-based Intent Resolver — no LLM / no OpenAI.
 */
final class IntentResolver
{
    public function __construct(
        private readonly IntentCatalog $catalog = new IntentCatalog(),
        private readonly float $matchThreshold = 0.55,
        private readonly float $ambiguousCeiling = 0.75,
    ) {}

    public function resolve(PlanningContext $context): IntentResolution
    {
        $text = mb_strtolower($context->messageText());
        $matches = [];

        foreach ($this->catalog->rules() as $rule) {
            $hits = [];
            foreach ($rule['keywords'] as $keyword) {
                if ($keyword !== '' && mb_strpos($text, mb_strtolower($keyword)) !== false) {
                    $hits[] = $keyword;
                }
            }
            if ($hits === []) {
                continue;
            }

            $confidence = min(1.0, 0.45 + (0.2 * count($hits)));
            $matches[] = [
                'intent' => new ResolvedIntent(
                    IntentCode::fromString($rule['code']),
                    $confidence,
                    $hits,
                ),
                'rule' => $rule,
            ];
        }

        if ($matches === []) {
            return IntentResolution::unknown('no keyword signals matched');
        }

        usort(
            $matches,
            static fn (array $a, array $b): int => $b['intent']->confidence() <=> $a['intent']->confidence()
        );

        /** @var list<ResolvedIntent> $intents */
        $intents = array_map(static fn (array $m): ResolvedIntent => $m['intent'], $matches);
        $codes = array_map(static fn (ResolvedIntent $i): string => $i->code()->toString(), $intents);

        if ($this->hasConflict($matches)) {
            return IntentResolution::of(
                IntentKind::Conflicting,
                $intents,
                $this->overall($intents),
                'conflicting write intents detected',
            );
        }

        if (count($intents) === 1) {
            $only = $intents[0];
            if ($only->confidence() < $this->matchThreshold) {
                return IntentResolution::of(
                    IntentKind::Ambiguous,
                    $intents,
                    $only->confidence(),
                    'single weak match below threshold',
                );
            }

            return IntentResolution::of(
                IntentKind::Single,
                $intents,
                $only->confidence(),
                'single intent matched',
            );
        }

        // Multi: keep intents above threshold; if top two close and weak → ambiguous
        $strong = array_values(array_filter(
            $intents,
            fn (ResolvedIntent $i): bool => $i->confidence() >= $this->matchThreshold
        ));

        if ($strong === []) {
            return IntentResolution::of(
                IntentKind::Ambiguous,
                $intents,
                $this->overall($intents),
                'multiple weak matches',
            );
        }

        if (count($strong) === 1 && $strong[0]->confidence() < $this->ambiguousCeiling && count($intents) > 1) {
            return IntentResolution::of(
                IntentKind::Ambiguous,
                $intents,
                $this->overall($intents),
                'competing signals without clear winner',
            );
        }

        return IntentResolution::of(
            IntentKind::Multi,
            $strong,
            $this->overall($strong),
            'multiple actionable intents: '.implode(',', $codes),
        );
    }

    /**
     * @param  list<array{intent: ResolvedIntent, rule: array<string, mixed>}>  $matches
     */
    private function hasConflict(array $matches): bool
    {
        $codes = [];
        foreach ($matches as $match) {
            $codes[$match['intent']->code()->toString()] = $match['rule'];
        }

        foreach ($codes as $code => $rule) {
            /** @var list<string> $conflicts */
            $conflicts = $rule['conflict_with'] ?? [];
            foreach ($conflicts as $other) {
                if (isset($codes[$other])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<ResolvedIntent>  $intents
     */
    private function overall(array $intents): float
    {
        if ($intents === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($intents as $intent) {
            $sum += $intent->confidence();
        }

        return round($sum / count($intents), 4);
    }
}
