<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Support\AiSales\AiSalesConversationState;
use InvalidArgumentException;

final class AiSalesStateMachine
{
    /**
     * @return array<string, list<AiSalesConversationState>>
     */
    public function allowed(): array
    {
        $anyExit = [
            AiSalesConversationState::HumanHandoff,
            AiSalesConversationState::Lost,
            AiSalesConversationState::Unqualified,
        ];

        return [
            AiSalesConversationState::New->value => [
                AiSalesConversationState::Discovery,
                AiSalesConversationState::Qualification,
                AiSalesConversationState::Recommendation,
                AiSalesConversationState::Objection,
                AiSalesConversationState::Consideration,
                AiSalesConversationState::DemoRequested,
                AiSalesConversationState::Trial,
                AiSalesConversationState::Checkout,
                ...$anyExit,
            ],
            AiSalesConversationState::Discovery->value => [
                AiSalesConversationState::Qualification,
                AiSalesConversationState::Recommendation,
                AiSalesConversationState::Objection,
                AiSalesConversationState::Consideration,
                AiSalesConversationState::DemoRequested,
                AiSalesConversationState::Trial,
                AiSalesConversationState::Checkout,
                AiSalesConversationState::Discovery,
                ...$anyExit,
            ],
            AiSalesConversationState::Qualification->value => [
                AiSalesConversationState::Recommendation,
                AiSalesConversationState::Discovery,
                AiSalesConversationState::Objection,
                AiSalesConversationState::DemoRequested,
                AiSalesConversationState::Trial,
                AiSalesConversationState::Checkout,
                ...$anyExit,
            ],
            AiSalesConversationState::Recommendation->value => [
                AiSalesConversationState::Objection,
                AiSalesConversationState::Consideration,
                AiSalesConversationState::DemoRequested,
                AiSalesConversationState::Trial,
                AiSalesConversationState::Checkout,
                AiSalesConversationState::Recommendation,
                ...$anyExit,
            ],
            AiSalesConversationState::Objection->value => [
                AiSalesConversationState::Recommendation,
                AiSalesConversationState::Consideration,
                AiSalesConversationState::DemoRequested,
                AiSalesConversationState::Trial,
                AiSalesConversationState::Checkout,
                ...$anyExit,
            ],
            AiSalesConversationState::Consideration->value => [
                AiSalesConversationState::Checkout,
                AiSalesConversationState::DemoRequested,
                AiSalesConversationState::Trial,
                AiSalesConversationState::Recommendation,
                AiSalesConversationState::Objection,
                AiSalesConversationState::Consideration,
                ...$anyExit,
            ],
            AiSalesConversationState::DemoRequested->value => [
                AiSalesConversationState::Trial,
                AiSalesConversationState::Checkout,
                AiSalesConversationState::Consideration,
                AiSalesConversationState::Won,
                ...$anyExit,
            ],
            AiSalesConversationState::Trial->value => [
                AiSalesConversationState::Checkout,
                AiSalesConversationState::Won,
                AiSalesConversationState::Consideration,
                ...$anyExit,
            ],
            AiSalesConversationState::Checkout->value => [
                AiSalesConversationState::Won,
                AiSalesConversationState::Consideration,
                ...$anyExit,
            ],
            AiSalesConversationState::HumanHandoff->value => [
                AiSalesConversationState::Discovery,
                AiSalesConversationState::Recommendation,
                AiSalesConversationState::Consideration,
                AiSalesConversationState::Won,
                AiSalesConversationState::Lost,
            ],
            AiSalesConversationState::Won->value => [AiSalesConversationState::Won],
            AiSalesConversationState::Lost->value => [AiSalesConversationState::Lost],
            AiSalesConversationState::Unqualified->value => [AiSalesConversationState::Unqualified],
        ];
    }

    public function canTransition(AiSalesConversationState $from, AiSalesConversationState $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, $this->allowed()[$from->value] ?? [], true);
    }

    public function transition(AiSalesConversationState $from, AiSalesConversationState $to): AiSalesConversationState
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(sprintf('Illegal sales state jump %s → %s.', $from->value, $to->value));
        }

        return $to;
    }
}
