<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

/**
 * Heuristic scorer for candidate customer matches (no AI).
 */
final class IdentityMatcher
{
    public function __construct(
        private readonly ConfidenceRules $rules = new ConfidenceRules(),
    ) {}

    public function rules(): ConfidenceRules
    {
        return $this->rules;
    }

    /**
     * @param  list<array{customer_id: CustomerId, score: float, reason: string}>  $candidates
     */
    public function match(array $candidates): IdentityMatchResult
    {
        if ($candidates === []) {
            return IdentityMatchResult::none();
        }

        usort(
            $candidates,
            static fn (array $a, array $b): int => $b['score'] <=> $a['score']
        );

        $top = $candidates[0];
        $score = ConfidenceScore::of($top['score']);
        $decision = $this->rules->decide($score, count($candidates));

        /** @var list<CustomerId> $ids */
        $ids = array_map(static fn (array $c): CustomerId => $c['customer_id'], $candidates);

        return match ($decision) {
            MatchDecision::HighConfidenceMatch => IdentityMatchResult::high($top['customer_id'], $score, $top['reason']),
            MatchDecision::RequiresHumanVerification => IdentityMatchResult::requiresHuman($top['customer_id'], $score, $top['reason']),
            MatchDecision::LowConfidenceMatch => IdentityMatchResult::low($top['customer_id'], $score, $top['reason']),
            MatchDecision::Conflict => IdentityMatchResult::conflict($ids, $score, 'multiple candidates above suggest threshold'),
            MatchDecision::NoMatch => IdentityMatchResult::none($top['reason']),
            MatchDecision::ExactLink => IdentityMatchResult::exact($top['customer_id']),
        };
    }
}
