<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Pipeline\Stages;

use DressnMore\Aos\Memory\Domain\Factory\MemoryFactory;
use DressnMore\Aos\Memory\Domain\Memory\MemoryFactExtractor;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteBag;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteStage;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteStageInterface;
use DressnMore\Aos\Memory\Domain\Policies\MemoryPolicy;

final class ExtractAndClassifyStage implements MemoryWriteStageInterface
{
    public function __construct(
        private readonly MemoryFactExtractor $extractor,
        private readonly MemoryFactory $factory,
        private readonly MemoryPolicy $policy,
    ) {}

    public function name(): MemoryWriteStage
    {
        return MemoryWriteStage::MemoryClassification;
    }

    public function process(MemoryWriteBag $bag): void
    {
        $update = $bag->update();
        $raw = $this->extractor->extract($update);
        $bag->setCandidates($raw);
        $bag->mark(MemoryWriteStage::ExtractCandidateFacts->value);

        $classified = [];
        foreach ($raw as $fact) {
            $record = $this->factory->create(
                $fact['type'],
                $fact['content'],
                $update->tenantId(),
                $update->customerId(),
                $fact['importance'],
                $fact['confidence'],
                tags: $fact['tags'],
                metadata: [
                    'correlation_id' => $update->correlationId(),
                ],
                sourceConversationId: $update->conversationId(),
                sourceMessageId: $update->messageId(),
            );

            if ($this->policy->privacyRedactsPiiHints()) {
                $content = (string) preg_replace('/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/', '[REDACTED]', $record->content());
                if ($content !== $record->content()) {
                    $record = $record->withContent($content);
                }
            }

            $classified[] = $record;
        }

        $bag->setClassified($classified);
        $bag->mark(MemoryWriteStage::PolicyEvaluation->value);
        $bag->mark(MemoryWriteStage::ImportanceScoring->value);

        $accepted = [];
        foreach ($classified as $record) {
            if ($this->policy->allowsPersist($record)) {
                $accepted[] = $record;
            } else {
                $bag->addDiscardReason('policy:'.$record->id()->toString());
            }
        }
        $bag->setAccepted($accepted);
    }
}
