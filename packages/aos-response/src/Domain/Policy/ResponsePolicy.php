<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Policy;

/**
 * Response safety policies — never leak internals to the user.
 */
final class ResponsePolicy
{
    public function sanitizeTechnicalMessage(string $message): string
    {
        $message = preg_replace('/\b(Exception|Error|Stack|Trace|PDO|SQLSTATE)\b/i', '', $message) ?? $message;
        $message = preg_replace('/\s+/', ' ', trim($message)) ?? '';

        if ($message === '' || str_contains($message, '\\') || str_contains($message, '::')) {
            return '';
        }

        return $message;
    }

    public function allowPayloadKey(string $key): bool
    {
        $blocked = ['password', 'token', 'secret', 'stack', 'trace', 'exception', 'sql'];

        foreach ($blocked as $needle) {
            if (str_contains(strtolower($key), $needle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function filterPayload(array $payload): array
    {
        $out = [];
        foreach ($payload as $key => $value) {
            if (! is_string($key) || ! $this->allowPayloadKey($key)) {
                continue;
            }
            if (is_array($value)) {
                $out[$key] = $this->filterPayload($value);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
