<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Pipeline;

use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Ai\Domain\Response\AiResponse;
use DressnMore\Aos\Ai\Domain\Selection\ProviderSelection;
use DressnMore\Aos\Ai\Domain\Streaming\StreamChunk;

final class AiPipelineBag
{
    /** @var list<ModelCapability> */
    private array $requiredCapabilities = [];

    /** @var list<ProviderDescriptor> */
    private array $providerCandidates = [];

    /** @var list<ModelDescriptor> */
    private array $modelCandidates = [];

    /** @var list<ProviderSelection> */
    private array $rankedSelections = [];

    private ?ProviderSelection $selection = null;

    private ?AiResponse $response = null;

    /** @var list<StreamChunk> */
    private array $streamChunks = [];

    /** @var list<string> */
    private array $stages = [];

    /** @var list<string> */
    private array $rejectionNotes = [];

    private bool $fallbackUsed = false;

    public function __construct(
        private AiRequest $request,
    ) {}

    public function request(): AiRequest
    {
        return $this->request;
    }

    public function replaceRequest(AiRequest $request): void
    {
        $this->request = $request;
    }

    public function mark(string $stage): void
    {
        $this->stages[] = $stage;
    }

    /** @return list<string> */
    public function stages(): array
    {
        return $this->stages;
    }

    /** @param  list<ModelCapability>  $capabilities */
    public function setRequiredCapabilities(array $capabilities): void
    {
        $this->requiredCapabilities = $capabilities;
    }

    /** @return list<ModelCapability> */
    public function requiredCapabilities(): array
    {
        return $this->requiredCapabilities;
    }

    /** @param  list<ProviderDescriptor>  $providers */
    public function setProviderCandidates(array $providers): void
    {
        $this->providerCandidates = $providers;
    }

    /** @return list<ProviderDescriptor> */
    public function providerCandidates(): array
    {
        return $this->providerCandidates;
    }

    /** @param  list<ModelDescriptor>  $models */
    public function setModelCandidates(array $models): void
    {
        $this->modelCandidates = $models;
    }

    /** @return list<ModelDescriptor> */
    public function modelCandidates(): array
    {
        return $this->modelCandidates;
    }

    /** @param  list<ProviderSelection>  $selections */
    public function setRankedSelections(array $selections): void
    {
        $this->rankedSelections = $selections;
    }

    /** @return list<ProviderSelection> */
    public function rankedSelections(): array
    {
        return $this->rankedSelections;
    }

    public function setSelection(?ProviderSelection $selection): void
    {
        $this->selection = $selection;
    }

    public function selection(): ?ProviderSelection
    {
        return $this->selection;
    }

    public function setResponse(AiResponse $response): void
    {
        $this->response = $response;
    }

    public function response(): ?AiResponse
    {
        return $this->response;
    }

    /** @param  list<StreamChunk>  $chunks */
    public function setStreamChunks(array $chunks): void
    {
        $this->streamChunks = $chunks;
    }

    /** @return list<StreamChunk> */
    public function streamChunks(): array
    {
        return $this->streamChunks;
    }

    public function addRejection(string $note): void
    {
        $this->rejectionNotes[] = $note;
    }

    /** @return list<string> */
    public function rejectionNotes(): array
    {
        return $this->rejectionNotes;
    }

    public function markFallbackUsed(): void
    {
        $this->fallbackUsed = true;
    }

    public function fallbackUsed(): bool
    {
        return $this->fallbackUsed;
    }
}
