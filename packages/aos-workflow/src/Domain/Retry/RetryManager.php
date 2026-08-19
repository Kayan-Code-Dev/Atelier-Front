<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Retry;

final class RetryManager
{
    public function nextDelaySeconds(RetryPolicyType $policy, int $attempt): int
    {
        return match ($policy) {
            RetryPolicyType::Immediate => 0,
            RetryPolicyType::ExponentialBackoff => (int) pow(2, max(0, $attempt - 1)),
            RetryPolicyType::ManualRetry => -1,
            RetryPolicyType::DeadLetter => -2,
        };
    }
}
