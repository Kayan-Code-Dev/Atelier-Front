<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Meta;

/**
 * Parses Instagram webhooks (DMs + comments when present).
 */
final class MetaInstagramWebhookPayloadParser
{
    /**
     * @param array<string, mixed> $payload
     * @return list<array{ig_id:string,kind:string,payload:array<string,mixed>}>
     */
    public function extract(array $payload): array
    {
        if (($payload['object'] ?? '') !== 'instagram') {
            return [];
        }

        $out = [];
        $entries = $payload['entry'] ?? [];
        if (! is_array($entries)) {
            return [];
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $igId = (string) ($entry['id'] ?? '');
            if ($igId === '') {
                continue;
            }

            $messaging = $entry['messaging'] ?? [];
            if (is_array($messaging)) {
                foreach ($messaging as $event) {
                    if (! is_array($event) || ! isset($event['message']) || ! is_array($event['message'])) {
                        continue;
                    }
                    if (! empty($event['message']['is_echo'])) {
                        continue;
                    }
                    $out[] = [
                        'ig_id' => $igId,
                        'kind' => 'message',
                        'payload' => [
                            'id' => (string) ($event['message']['mid'] ?? uniqid('ig_msg_', true)),
                            'from' => (string) ($event['sender']['id'] ?? ''),
                            'text' => (string) ($event['message']['text'] ?? ''),
                            'type' => 'text',
                            'raw' => $event,
                        ],
                    ];
                }
            }

            $changes = $entry['changes'] ?? [];
            if (is_array($changes)) {
                foreach ($changes as $change) {
                    if (! is_array($change)) {
                        continue;
                    }
                    $field = (string) ($change['field'] ?? '');
                    $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                    if (! in_array($field, ['comments', 'live_comments'], true)) {
                        continue;
                    }
                    $from = is_array($value['from'] ?? null) ? $value['from'] : [];
                    $out[] = [
                        'ig_id' => $igId,
                        'kind' => 'comment',
                        'payload' => [
                            'id' => (string) ($value['id'] ?? uniqid('ig_cmt_', true)),
                            'post_id' => (string) ($value['media']['id'] ?? $value['media_id'] ?? ''),
                            'from' => (string) ($from['id'] ?? 'unknown'),
                            'text' => (string) ($value['text'] ?? $value['message'] ?? ''),
                            'raw' => $value,
                        ],
                    ];
                }
            }
        }

        return $out;
    }
}
