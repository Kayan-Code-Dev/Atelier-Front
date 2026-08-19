<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Response\Contracts\ErrorResponseInterface;
use DressnMore\Aos\Response\Contracts\LocalizationInterface;
use DressnMore\Aos\Response\Domain\Aggregator\ToolOutcome;
use DressnMore\Aos\Response\Domain\Policy\ResponsePolicy;
use DressnMore\Aos\Response\Domain\Response\FinalAiResponse;
use DressnMore\Aos\Response\Domain\Response\ResponseContext;
use DressnMore\Aos\Response\Domain\Response\ResponseStatus;

final class ErrorResponseGenerator implements ErrorResponseInterface
{
    /** @var array<string, string> */
    private const CODE_KEYS = [
        'dress_unavailable' => 'error.dress_unavailable',
        'DRESS_UNAVAILABLE' => 'error.dress_unavailable',
        'ReservationToolException' => 'error.dress_unavailable',
        'customer_not_found' => 'error.customer_not_found',
        'permission_denied' => 'error.permission_denied',
        'validation_failed' => 'error.validation',
        'VALIDATION_FAILED' => 'error.validation',
    ];

    public function __construct(
        private readonly LocalizationInterface $i18n,
        private readonly ResponsePolicy $policy = new ResponsePolicy(),
    ) {}

    public function fromOutcome(ResponseContext $context, ToolOutcome $outcome): FinalAiResponse
    {
        return $this->fromFailures($context, [$outcome]);
    }

    public function fromFailures(ResponseContext $context, array $failed): FinalAiResponse
    {
        $i18n = $this->i18n->withLocale($context->locale());
        $sections = [];
        $firstCode = null;

        foreach ($failed as $outcome) {
            $code = $outcome->primaryErrorCode() ?? 'generic';
            $firstCode ??= $code;
            $key = self::CODE_KEYS[$code] ?? 'error.generic';
            $msg = $i18n->translate($key, [], $context->locale());

            // Prefer mapped friendly text; never expose raw exception class names.
            $raw = $this->policy->sanitizeTechnicalMessage((string) $outcome->primaryErrorMessage());
            if ($key === 'error.generic' && $raw !== '' && ! str_contains($raw, 'Exception')) {
                $msg = $raw;
            }
            $sections[] = $msg;
        }

        $message = $sections[0] ?? $i18n->translate('generic_failure', [], $context->locale());
        if (count($sections) > 1) {
            $message = implode(' ', $sections);
        }

        return new FinalAiResponse(
            $message,
            ResponseStatus::Failed,
            $context->locale(),
            $sections,
            ['error_code' => $firstCode],
            [],
            $context->planId(),
            $context->correlationId(),
        );
    }
}
