<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Response\Contracts\ErrorResponseInterface;
use DressnMore\Aos\Response\Contracts\LocalizationInterface;
use DressnMore\Aos\Response\Contracts\ResponseBuilderInterface;
use DressnMore\Aos\Response\Domain\Aggregator\AggregatedToolResults;
use DressnMore\Aos\Response\Domain\Policy\ResponsePolicy;
use DressnMore\Aos\Response\Domain\Response\FinalAiResponse;
use DressnMore\Aos\Response\Domain\Response\ResponseContext;
use DressnMore\Aos\Response\Domain\Response\ResponseStatus;

final class ResponseBuilder implements ResponseBuilderInterface
{
    public function __construct(
        private readonly LocalizationInterface $i18n,
        private readonly ResultFormatter $formatter,
        private readonly ErrorResponseInterface $errors,
        private readonly ResponsePolicy $policy = new ResponsePolicy(),
    ) {}

    public function build(ResponseContext $context, AggregatedToolResults $results): FinalAiResponse
    {
        $locale = $context->locale();
        $i18n = $this->i18n->withLocale($locale);

        if ($results->isEmpty()) {
            return new FinalAiResponse(
                $i18n->translate('empty', [], $locale),
                ResponseStatus::Empty,
                $locale,
                [],
                [],
                [],
                $context->planId(),
                $context->correlationId(),
            );
        }

        if ($results->allFailed()) {
            return $this->errors->fromFailures($context, $results->failed());
        }

        $sections = [];
        $data = [];
        foreach ($results->succeeded() as $outcome) {
            $sections[] = $this->formatter->format($outcome, $locale);
            $data[$outcome->toolName()] = $this->policy->filterPayload($outcome->payload());
        }

        $status = $results->isPartial() ? ResponseStatus::PartialSuccess : ResponseStatus::Success;
        $message = $sections[0] ?? $i18n->translate('generic_success', [], $locale);

        if (count($sections) > 1) {
            $message = $i18n->translate('multi.prefix', [], $locale)."\n".implode("\n", array_map(
                static fn (string $s): string => '• '.$s,
                $sections,
            ));
        }

        if ($results->isPartial()) {
            $errorPart = $this->errors->fromFailures($context, $results->failed());
            $message = $i18n->translate('generic_partial', [], $locale)."\n".$message."\n".$errorPart->message();
            foreach ($errorPart->sections() as $section) {
                $sections[] = $section;
            }
        }

        return new FinalAiResponse(
            $message,
            $status,
            $locale,
            $sections,
            $data,
            [],
            $context->planId(),
            $context->correlationId(),
        );
    }
}
