<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Knowledge;

final class KnowledgeDocument
{
    public function __construct(
        private readonly string $id,
        private readonly string $knowledgeBaseId,
        private readonly string $tenantId,
        private readonly string $title,
        private readonly string $contentRef = '',
    ) {}

    public function id(): string { return $this->id; }
    public function knowledgeBaseId(): string { return $this->knowledgeBaseId; }
    public function tenantId(): string { return $this->tenantId; }
    public function title(): string { return $this->title; }
    public function contentRef(): string { return $this->contentRef; }
}
