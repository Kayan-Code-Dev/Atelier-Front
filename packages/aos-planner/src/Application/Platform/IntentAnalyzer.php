<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Planner\Contracts\IntentAnalyzerInterface;
use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\PlatformIntentCatalog;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;

final class IntentAnalyzer implements IntentAnalyzerInterface
{
    public function __construct(private readonly PlatformIntentCatalog $catalog = new PlatformIntentCatalog()) {}

    public function analyze(PlatformPlanningContext $context): AnalyzedIntent
    {
        $text = mb_strtolower($context->message());
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
            $confidence = min(1.0, 0.5 + (0.15 * count($hits)));
            $matches[] = ['rule' => $rule, 'hits' => $hits, 'confidence' => $confidence];
        }

        if ($matches === []) {
            return AnalyzedIntent::unknown('no intent signals');
        }

        usort($matches, static fn (array $a, array $b): int => $b['confidence'] <=> $a['confidence']);

        // Conflicting write intents (book + cancel)
        $intents = array_map(static fn (array $m): string => $m['rule']['intent'], $matches);
        foreach ($matches as $match) {
            foreach ($match['rule']['conflicts'] ?? [] as $conflict) {
                if (in_array($conflict, $intents, true)) {
                    return new AnalyzedIntent(
                        'Conflicting',
                        $match['confidence'],
                        $match['hits'],
                        [],
                        [],
                        null,
                        null,
                        false,
                    );
                }
            }
        }

        $best = $matches[0];
        $rule = $best['rule'];

        return new AnalyzedIntent(
            $rule['intent'],
            $best['confidence'],
            $best['hits'],
            $rule['toolPlan'],
            $rule['capabilities'],
            $rule['policy'] ?? null,
            $rule['approval'] ?? null,
            true,
        );
    }
}
