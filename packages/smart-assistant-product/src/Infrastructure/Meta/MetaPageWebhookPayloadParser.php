<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Meta;

/**
 * Parses Facebook Page webhooks (Messenger + feed comments).
 */
final class MetaPageWebhookPayloadParser
{
    /**
     * @param array<string, mixed> $payload
     * @return list<array{page_id:string,kind:string,payload:array<string,mixed>}>
     */
    public function extract(array $payload): array
    {
        if (($payload['object'] ?? '') !== 'page') {
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
            $pageId = (string) ($entry['id'] ?? '');
            if ($pageId === '') {
                continue;
            }

            $messaging = $entry['messaging'] ?? [];
            if (is_array($messaging)) {
                foreach ($messaging as $event) {
                    if (! is_array($event) || ! isset($event['message']) || ! is_array($event['message'])) {
                        continue;
                    }
                    // Ignore echoes / deliveries.
                    if (! empty($event['message']['is_echo'])) {
                        continue;
                    }
                    $text = (string) ($event['message']['text'] ?? '');
                    $out[] = [
                        'page_id' => $pageId,
                        'kind' => 'message',
                        'payload' => [
                            'id' => (string) ($event['message']['mid'] ?? uniqid('fb_msg_', true)),
                            'from' => (string) ($event['sender']['id'] ?? ''),
                            'text' => $text,
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
                    if ($field !== 'feed') {
                        continue;
                    }
                    $item = (string) ($value['item'] ?? '');
                    $verb = (string) ($value['verb'] ?? 'add');
                    if ($item !== 'comment' || ! in_array($verb, ['add', 'edited'], true)) {
                        continue;
                    }
                    $from = is_array($value['from'] ?? null) ? $value['from'] : [];
                    $out[] = [
                        'page_id' => $pageId,
                        'kind' => 'comment',
                        'payload' => [
                            'id' => (string) ($value['comment_id'] ?? $value['id'] ?? uniqid('fb_cmt_', true)),
                            'post_id' => (string) ($value['post_id'] ?? $value['parent_id'] ?? ''),
                            'from' => (string) ($from['id'] ?? 'unknown'),
                            'text' => (string) ($value['message'] ?? ''),
                            'raw' => $value,
                        ],
                    ];
                }
            }
        }

        return $out;
    }
}
