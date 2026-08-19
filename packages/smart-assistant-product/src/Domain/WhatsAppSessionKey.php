<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Domain;

/**
 * Gateway session id: legacy "{tenantId}" or multi-number "{tenantId}c{connectionId}".
 */
final class WhatsAppSessionKey
{
    public static function forConnection(int $tenantId, int $connectionId): string
    {
        return $tenantId.'c'.$connectionId;
    }

    public static function legacy(int $tenantId): string
    {
        return (string) $tenantId;
    }

    /**
     * @return array{tenant_id:int, connection_id:?int, session_key:string}
     */
    public static function parse(string $raw): array
    {
        $raw = trim($raw);
        if (preg_match('/^(\d+)c(\d+)$/', $raw, $m) === 1) {
            return [
                'tenant_id' => (int) $m[1],
                'connection_id' => (int) $m[2],
                'session_key' => $raw,
            ];
        }

        if (preg_match('/^(\d+)$/', $raw, $m) === 1) {
            return [
                'tenant_id' => (int) $m[1],
                'connection_id' => null,
                'session_key' => $raw,
            ];
        }

        if (preg_match('/^(\d+)/', $raw, $m) === 1) {
            return [
                'tenant_id' => (int) $m[1],
                'connection_id' => null,
                'session_key' => $raw,
            ];
        }

        return [
            'tenant_id' => 0,
            'connection_id' => null,
            'session_key' => $raw,
        ];
    }
}
