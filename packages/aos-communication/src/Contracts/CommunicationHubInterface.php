<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Contracts;

use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;
use DressnMore\Aos\Communication\Domain\Pipeline\MessagePipelineBag;

interface CommunicationHubInterface
{
    /**
     * @param array<string,mixed> $payload
     */
    public function receive(array $payload, ?string $tenantId = null): MessagePipelineBag;

    public function send(NormalizedMessage $message): bool;
}
