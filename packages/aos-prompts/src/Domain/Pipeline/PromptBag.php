<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Pipeline;

use DressnMore\Aos\Prompts\Domain\Guard\GuardResult;
use DressnMore\Aos\Prompts\Domain\Persona\Persona;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptDocument;
use DressnMore\Aos\Prompts\Domain\Section\PromptSection;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplate;
use DressnMore\Aos\Prompts\Domain\Validation\ValidationResult;

final class PromptBag
{
    private ?Persona $persona = null;

    private ?PromptTemplate $template = null;

    /** @var list<PromptSection> */
    private array $sections = [];

    private ?string $rendered = null;

    private ?PromptDocument $document = null;

    private ?GuardResult $guardResult = null;

    private ?ValidationResult $validation = null;

    private bool $rejected = false;

    private string $rejectionReason = '';

    /** @var list<string> */
    private array $stages = [];

    public function __construct(
        private PromptBuildRequest $request,
    ) {}

    public function request(): PromptBuildRequest
    {
        return $this->request;
    }

    public function replaceRequest(PromptBuildRequest $request): void
    {
        $this->request = $request;
    }

    public function mark(string $stage): void
    {
        $this->stages[] = $stage;
    }

    /**
     * @return list<string>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    public function setPersona(Persona $persona): void
    {
        $this->persona = $persona;
    }

    public function persona(): ?Persona
    {
        return $this->persona;
    }

    public function setTemplate(?PromptTemplate $template): void
    {
        $this->template = $template;
    }

    public function template(): ?PromptTemplate
    {
        return $this->template;
    }

    /**
     * @param  list<PromptSection>  $sections
     */
    public function setSections(array $sections): void
    {
        $this->sections = $sections;
    }

    /**
     * @return list<PromptSection>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    public function setRendered(string $rendered): void
    {
        $this->rendered = $rendered;
    }

    public function rendered(): ?string
    {
        return $this->rendered;
    }

    public function setDocument(PromptDocument $document): void
    {
        $this->document = $document;
    }

    public function document(): ?PromptDocument
    {
        return $this->document;
    }

    public function setGuardResult(GuardResult $result): void
    {
        $this->guardResult = $result;
    }

    public function guardResult(): ?GuardResult
    {
        return $this->guardResult;
    }

    public function setValidation(ValidationResult $result): void
    {
        $this->validation = $result;
    }

    public function validation(): ?ValidationResult
    {
        return $this->validation;
    }

    public function reject(string $reason): void
    {
        $this->rejected = true;
        $this->rejectionReason = $reason;
    }

    public function isRejected(): bool
    {
        return $this->rejected;
    }

    public function rejectionReason(): string
    {
        return $this->rejectionReason;
    }
}
