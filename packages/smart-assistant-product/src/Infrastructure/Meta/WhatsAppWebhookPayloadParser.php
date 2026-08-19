<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Meta;

/**
 * Parses WhatsApp Cloud API webhook payloads into normalized inbound messages.
 */
final class WhatsAppWebhookPayloadParser
{
    /**
     * @param array<string, mixed> $payload
     * @return list<array{phone_number_id:string,message:array<string,mixed>}>
     */
    public function extractInboundMessages(array $payload): array
    {
        if (($payload['object'] ?? '') !== 'whatsapp_business_account' && ! isset($payload['entry'])) {
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
            $changes = $entry['changes'] ?? [];
            if (! is_array($changes)) {
                continue;
            }
            foreach ($changes as $change) {
                if (! is_array($change)) {
                    continue;
                }
                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');
                $messages = $value['messages'] ?? [];
                $contacts = [];
                foreach (is_array($value['contacts'] ?? null) ? $value['contacts'] : [] as $contact) {
                    if (! is_array($contact)) {
                        continue;
                    }
                    $waId = (string) ($contact['wa_id'] ?? '');
                    $pushName = trim((string) ($contact['profile']['name'] ?? ''));
                    if ($waId !== '') {
                        $contacts[$waId] = $pushName;
                    }
                }
                if (! is_array($messages) || $phoneNumberId === '') {
                    continue;
                }
                foreach ($messages as $message) {
                    if (! is_array($message)) {
                        continue;
                    }
                    $type = (string) ($message['type'] ?? 'text');
                    $text = '';
                    if ($type === 'text') {
                        $text = (string) ($message['text']['body'] ?? '');
                    } elseif ($type === 'button') {
                        $text = (string) ($message['button']['text'] ?? '');
                    } elseif ($type === 'interactive') {
                        $text = (string) ($message['interactive']['button_reply']['title']
                            ?? $message['interactive']['list_reply']['title']
                            ?? '');
                    }

                    $from = (string) ($message['from'] ?? '');
                    $out[] = [
                        'phone_number_id' => $phoneNumberId,
                        'message' => [
                            'id' => (string) ($message['id'] ?? ''),
                            'from' => $from,
                            'text' => $text,
                            'type' => $type,
                            'timestamp' => (string) ($message['timestamp'] ?? ''),
                            'push_name' => $contacts[$from] ?? '',
                            'raw' => $message,
                        ],
                    ];
                }
            }
        }

        return $out;
    }
}
