<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Guard;

use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;

/**
 * Prompt Guard — injection / isolation / unsafe instruction protection.
 */
final class PromptGuard
{
    /** @var list<string> */
    private const INJECTION_PATTERNS = [
        'ignore previous instructions',
        'ignore all instructions',
        'disregard your system prompt',
        'you are now',
        'jailbreak',
        'dan mode',
        'override system',
        'تجاهل التعليمات',
        'تجاهل كل التعليمات',
        'انسى التعليمات',
    ];

    /** @var list<string> */
    private const SENSITIVE_PATTERNS = [
        '/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/', // card-like
        '/\b(?:password|secret|api[_-]?key)\s*[:=]\s*\S+/i',
    ];

    public function inspect(PromptBuildRequest $request): GuardResult
    {
        $triggers = [];
        $message = $request->userMessage();
        $lower = mb_strtolower($message);

        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (mb_strpos($lower, mb_strtolower($pattern)) !== false) {
                $triggers[] = 'injection:'.$pattern;
            }
        }

        if ($triggers !== []) {
            return GuardResult::reject($triggers);
        }

        $sanitized = $message;
        foreach (self::SENSITIVE_PATTERNS as $regex) {
            if (preg_match($regex, $sanitized) === 1) {
                $triggers[] = 'sensitive_pattern';
                $sanitized = (string) preg_replace($regex, '[REDACTED]', $sanitized);
            }
        }

        // Tenant isolation marker: forbid cross-tenant instruction dumps in user message.
        if ($request->tenantId() !== null
            && preg_match('/tenant\s*[:=]\s*[a-z0-9_-]+/i', $message) === 1
            && ! str_contains(mb_strtolower($message), mb_strtolower($request->tenantId()))
        ) {
            $triggers[] = 'tenant_isolation';

            return GuardResult::reject($triggers);
        }

        if ($triggers !== []) {
            return GuardResult::sanitize($sanitized, $triggers);
        }

        return GuardResult::allow();
    }
}
