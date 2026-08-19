<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Validation;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;

final class KnowledgeValidator
{
    /**
     * @return list<string>
     */
    public function validate(KnowledgeDocument $document): array
    {
        $errors = [];
        if (trim($document->title()) === '') {
            $errors[] = 'missing_title';
        }
        if (mb_strlen(trim($document->body())) < 10) {
            $errors[] = 'body_too_short';
        }
        if ($document->language() === '') {
            $errors[] = 'missing_language';
        }
        if ($document->confidence() < 0.2) {
            $errors[] = 'confidence_too_low';
        }

        return $errors;
    }

    public function isValid(KnowledgeDocument $document): bool
    {
        return $this->validate($document) === [];
    }
}
