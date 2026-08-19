<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

/**
 * Heuristic fact extractor — never persists raw utterances as durable memory.
 */
final class MemoryFactExtractor
{
    /**
     * @return list<array{content: string, type: MemoryType, importance: float, confidence: float, tags: list<string>}>
     */
    public function extract(ConversationMemoryUpdate $update): array
    {
        $facts = [];

        foreach ($update->candidateHints() as $hint) {
            $hint = trim($hint);
            if ($hint === '' || $this->looksLikeRawTranscript($hint)) {
                continue;
            }
            $facts[] = $this->classify($hint);
        }

        $utterance = trim($update->userUtterance());
        if ($utterance !== '' && ! $this->looksLikeRawTranscript($utterance)) {
            foreach ($this->deriveFromUtterance($utterance) as $derived) {
                $facts[] = $derived;
            }
        }

        // Always keep a working-memory pointer (classified, not raw dump).
        if ($utterance !== '') {
            $facts[] = [
                'content' => 'Recent intent signal: '.$this->clip($utterance, 120),
                'type' => MemoryType::Working,
                'importance' => 0.4,
                'confidence' => 0.55,
                'tags' => ['working', 'intent_signal'],
            ];
        }

        return $facts;
    }

    /**
     * @return list<array{content: string, type: MemoryType, importance: float, confidence: float, tags: list<string>}>
     */
    private function deriveFromUtterance(string $utterance): array
    {
        $lower = mb_strtolower($utterance);
        $facts = [];

        if (preg_match('/(?:اسمي|my name is)\s+([^\s,.!?]+)/iu', $utterance, $m) === 1) {
            $facts[] = [
                'content' => 'Customer name preference: '.$m[1],
                'type' => MemoryType::Preference,
                'importance' => 0.8,
                'confidence' => 0.75,
                'tags' => ['preference', 'name'],
            ];
        }

        if (str_contains($lower, 'شكوى') || str_contains($lower, 'complaint')) {
            $facts[] = [
                'content' => 'Customer expressed a complaint in this conversation.',
                'type' => MemoryType::Episodic,
                'importance' => 0.85,
                'confidence' => 0.7,
                'tags' => ['complaint', 'episodic'],
            ];
        }

        if (str_contains($lower, 'حجز') || str_contains($lower, 'reserv')) {
            $facts[] = [
                'content' => 'Customer is discussing a reservation.',
                'type' => MemoryType::Operational,
                'importance' => 0.7,
                'confidence' => 0.65,
                'tags' => ['reservation', 'operational'],
            ];
        }

        if (str_contains($lower, 'فاتورة') || str_contains($lower, 'invoice') || str_contains($lower, 'متبقي')) {
            $facts[] = [
                'content' => 'Customer inquired about billing or outstanding balance.',
                'type' => MemoryType::Business,
                'importance' => 0.75,
                'confidence' => 0.7,
                'tags' => ['billing', 'business'],
            ];
        }

        return $facts;
    }

    /**
     * @return array{content: string, type: MemoryType, importance: float, confidence: float, tags: list<string>}
     */
    private function classify(string $hint): array
    {
        $lower = mb_strtolower($hint);
        $type = MemoryType::ShortTerm;
        $importance = 0.55;
        $tags = ['hint'];

        if (str_contains($lower, 'prefer') || str_contains($lower, 'يفضل')) {
            $type = MemoryType::Preference;
            $importance = 0.8;
            $tags[] = 'preference';
        } elseif (str_contains($lower, 'customer') || str_contains($lower, 'عميل')) {
            $type = MemoryType::Customer;
            $importance = 0.7;
            $tags[] = 'customer';
        } elseif (str_contains($lower, 'business') || str_contains($lower, 'atelier')) {
            $type = MemoryType::Business;
            $importance = 0.65;
            $tags[] = 'business';
        }

        return [
            'content' => $this->clip($hint, 240),
            'type' => $type,
            'importance' => $importance,
            'confidence' => 0.7,
            'tags' => $tags,
        ];
    }

    private function looksLikeRawTranscript(string $text): bool
    {
        return (bool) preg_match('/^(user|assistant|system)\s*:/i', $text)
            || substr_count($text, "\n") > 6;
    }

    private function clip(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max).'…';
    }
}
