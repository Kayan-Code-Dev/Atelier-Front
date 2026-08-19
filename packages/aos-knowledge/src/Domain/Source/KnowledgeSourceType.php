<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Source;

enum KnowledgeSourceType: string
{
    case ManualEntry = 'manual_entry';
    case UploadedDocument = 'uploaded_document';
    case Pdf = 'pdf';
    case Word = 'word';
    case Markdown = 'markdown';
    case Html = 'html';
    case Website = 'website';
    case FutureApi = 'future_api';
    case FutureDatabase = 'future_database';
}
