<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Infrastructure\Bootstrap;

use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionScope;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollection;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollectionManager;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSource;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceManager;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceType;
use DressnMore\Aos\Knowledge\Domain\Source\SourceId;

final class BuiltinKnowledgeCatalogBootstrap
{
    public function __construct(
        private readonly KnowledgeCollectionManager $collections,
        private readonly KnowledgeSourceManager $sources,
    ) {}

    public function seed(): void
    {
        if ($this->collections->get(CollectionId::fromString('col_global')) === null) {
            $this->collections->register(new KnowledgeCollection(
                CollectionId::fromString('col_global'),
                'Global Collection',
                CollectionScope::Global,
                description: 'Platform-wide knowledge',
            ));
        }

        if ($this->sources->get(SourceId::fromString('src_manual')) === null) {
            $this->sources->register(new KnowledgeSource(
                SourceId::fromString('src_manual'),
                KnowledgeSourceType::ManualEntry,
                'Manual Entry',
            ));
        }

        foreach ([
            [KnowledgeSourceType::Pdf, 'src_pdf', 'PDF Uploads'],
            [KnowledgeSourceType::Markdown, 'src_md', 'Markdown'],
            [KnowledgeSourceType::Html, 'src_html', 'HTML'],
            [KnowledgeSourceType::Website, 'src_web', 'Website'],
            [KnowledgeSourceType::FutureApi, 'src_api', 'Future API Source'],
            [KnowledgeSourceType::FutureDatabase, 'src_db', 'Future Database Source'],
        ] as [$type, $id, $name]) {
            if ($this->sources->get(SourceId::fromString($id)) === null) {
                $this->sources->register(new KnowledgeSource(
                    SourceId::fromString($id),
                    $type,
                    $name,
                ));
            }
        }
    }
}
