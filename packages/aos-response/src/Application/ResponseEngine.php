<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Response\Contracts\ResponseBuilderInterface;
use DressnMore\Aos\Response\Contracts\ResponseEngineInterface;
use DressnMore\Aos\Response\Domain\Aggregator\AggregatedToolResults;
use DressnMore\Aos\Response\Domain\Events\ResponseFailed;
use DressnMore\Aos\Response\Domain\Events\ResponseGenerated;
use DressnMore\Aos\Response\Domain\Events\ResponseStarted;
use DressnMore\Aos\Response\Domain\Response\FinalAiResponse;
use DressnMore\Aos\Response\Domain\Response\ResponseContext;
use Throwable;

final class ResponseEngine implements ResponseEngineInterface
{
    public function __construct(
        private readonly ResponseBuilderInterface $builder,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function generate(ResponseContext $context, AggregatedToolResults $results): FinalAiResponse
    {
        $corr = $context->correlationId();

        try {
            $this->eventBus->publish(new ResponseStarted($corr, $context->planId(), $results->count()));
            $response = $this->builder->build($context, $results);
            $this->eventBus->publish(new ResponseGenerated($corr, $response->status()->value, $response->locale()));

            return $response;
        } catch (Throwable $e) {
            $this->eventBus->publish(new ResponseFailed($corr, 'engine_exception', 'response_generation_failed'));
            throw $e;
        }
    }
}
