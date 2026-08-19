<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Publishing;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeLifecycleStatus;
use DressnMore\Aos\Knowledge\Domain\Policies\KnowledgePolicyEngine;
use DressnMore\Aos\Knowledge\Domain\Validation\KnowledgeValidator;
use RuntimeException;

final class KnowledgePublisher
{
    public function __construct(
        private readonly KnowledgeValidator $validator = new KnowledgeValidator(),
        private readonly KnowledgePolicyEngine $policies = new KnowledgePolicyEngine(),
    ) {}

    public function submitForReview(KnowledgeDocument $document): KnowledgeDocument
    {
        return $document->withStatus(KnowledgeLifecycleStatus::Review);
    }

    public function approve(KnowledgeDocument $document): KnowledgeDocument
    {
        return $document->withStatus(KnowledgeLifecycleStatus::Approved);
    }

    public function publish(KnowledgeDocument $document): KnowledgeDocument
    {
        if (! $this->validator->isValid($document)) {
            throw new RuntimeException('Cannot publish invalid knowledge: '.implode(',', $this->validator->validate($document)));
        }

        if ($document->status() === KnowledgeLifecycleStatus::Published) {
            return $document;
        }

        $current = $document;
        if ($current->status() === KnowledgeLifecycleStatus::Draft) {
            $current = $current->withStatus(KnowledgeLifecycleStatus::Review);
        }
        if ($current->status() === KnowledgeLifecycleStatus::Review) {
            $current = $current->withStatus(KnowledgeLifecycleStatus::Approved);
        }
        if ($current->status() !== KnowledgeLifecycleStatus::Approved) {
            throw new RuntimeException('Knowledge must be approved before publish.');
        }

        return $current->withStatus(KnowledgeLifecycleStatus::Published);
    }

    public function archive(KnowledgeDocument $document): KnowledgeDocument
    {
        return $document->withStatus(KnowledgeLifecycleStatus::Archived);
    }

    public function deprecate(KnowledgeDocument $document): KnowledgeDocument
    {
        return $document->withStatus(KnowledgeLifecycleStatus::Deprecated);
    }
}
