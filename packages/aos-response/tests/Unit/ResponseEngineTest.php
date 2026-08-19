<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Tests\Unit;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Response\Application\EndToEndAiOrchestrator;
use DressnMore\Aos\Response\Application\ErrorResponseGenerator;
use DressnMore\Aos\Response\Application\LocalizationService;
use DressnMore\Aos\Response\Application\PlanStepExecutor;
use DressnMore\Aos\Response\Application\ResponseBuilder;
use DressnMore\Aos\Response\Application\ResponseEngine;
use DressnMore\Aos\Response\Application\ResultAggregator;
use DressnMore\Aos\Response\Application\ResultFormatter;
use DressnMore\Aos\Response\Application\ToolOutcomeFactory;
use DressnMore\Aos\Response\Domain\Policy\ResponsePolicy;
use DressnMore\Aos\Response\Domain\Response\ResponseContext;
use DressnMore\Aos\Response\Domain\Response\ResponseStatus;
use PHPUnit\Framework\TestCase;

final class ResponseEngineTest extends TestCase
{
    private EventBusInterface $bus;
    private ResponseEngine $engine;
    private ResultAggregator $aggregator;
    private ToolOutcomeFactory $outcomes;
    private LocalizationService $i18n;

    protected function setUp(): void
    {
        $this->bus = new class implements EventBusInterface {
            public function publish(object $event): void {}

            public function subscribe(string $event, string|callable $listener): void {}
        };
        $this->i18n = new LocalizationService('ar');
        $policy = new ResponsePolicy();
        $formatter = new ResultFormatter($this->i18n, $policy);
        $errors = new ErrorResponseGenerator($this->i18n, $policy);
        $builder = new ResponseBuilder($this->i18n, $formatter, $errors, $policy);
        $this->engine = new ResponseEngine($builder, $this->bus);
        $this->aggregator = new ResultAggregator();
        $this->outcomes = new ToolOutcomeFactory();
    }

    public function test_single_tool_success_arabic(): void
    {
        $agg = $this->aggregator->aggregate([
            $this->outcomes->success('CreateCustomer', ['name' => 'سارة أحمد'], 1),
        ]);
        $response = $this->engine->generate(new ResponseContext('ar', 'CreateCustomer'), $agg);

        $this->assertSame(ResponseStatus::Success, $response->status());
        $this->assertStringContainsString('سارة أحمد', $response->message());
        $this->assertSame('ar', $response->locale());
    }

    public function test_reservation_success_message(): void
    {
        $agg = $this->aggregator->aggregate([
            $this->outcomes->success('CreateReservation', ['day' => 'الجمعة', 'time' => '5:00 مساءً'], 1),
        ]);
        $response = $this->engine->generate(new ResponseContext('ar'), $agg);

        $this->assertStringContainsString('الجمعة', $response->message());
        $this->assertStringContainsString('5:00', $response->message());
    }

    public function test_report_success_message(): void
    {
        $agg = $this->aggregator->aggregate([
            $this->outcomes->success('GenerateReport', ['amount' => 12450, 'count' => 18], 1),
        ]);
        $response = $this->engine->generate(new ResponseContext('ar'), $agg);

        $this->assertStringContainsString('18', $response->message());
        $this->assertTrue($response->isSuccess());
    }

    public function test_multiple_tools_aggregated(): void
    {
        $agg = $this->aggregator->aggregate([
            $this->outcomes->success('CreateCustomer', ['name' => 'سارة أحمد'], 1),
            $this->outcomes->success('CreateReservation', ['day' => 'الجمعة', 'time' => '5:00 مساءً'], 2),
            $this->outcomes->success('CreateInvoice', ['invoice' => 'INV-9'], 3),
        ]);
        $response = $this->engine->generate(new ResponseContext('ar'), $agg);

        $this->assertSame(ResponseStatus::Success, $response->status());
        $this->assertGreaterThanOrEqual(3, count($response->sections()));
        $this->assertStringContainsString('سارة', $response->message());
        $this->assertStringContainsString('INV-9', $response->message());
    }

    public function test_tool_failure_friendly_arabic(): void
    {
        $agg = $this->aggregator->aggregate([
            $this->outcomes->failure('CreateReservation', 'ReservationToolException', 'ReservationToolException', 1),
        ]);
        $response = $this->engine->generate(new ResponseContext('ar'), $agg);

        $this->assertSame(ResponseStatus::Failed, $response->status());
        $this->assertStringContainsString('فستان', $response->message());
        $this->assertStringNotContainsString('Exception', $response->message());
    }

    public function test_empty_results(): void
    {
        $response = $this->engine->generate(new ResponseContext('ar'), $this->aggregator->aggregate([]));
        $this->assertSame(ResponseStatus::Empty, $response->status());
    }

    public function test_english_locale(): void
    {
        $agg = $this->aggregator->aggregate([
            $this->outcomes->success('CreateCustomer', ['name' => 'Sara'], 1),
        ]);
        $response = $this->engine->generate(new ResponseContext('en', 'CreateCustomer'), $agg);

        $this->assertSame('en', $response->locale());
        $this->assertStringContainsString('Sara', $response->message());
        $this->assertStringContainsString('created', strtolower($response->message()));
    }

    public function test_end_to_end_book_reservation(): void
    {
        $orchestrator = EndToEndAiOrchestrator::createDefault($this->bus, new PlanStepExecutor());
        $result = $orchestrator->handle('احجز فستان', 'tenant_demo', 'ar', 'conv_1');

        $this->assertSame('BookReservation', $result->plan()->intent());
        $this->assertTrue($result->plan()->isReadyForGateway());
        $this->assertGreaterThanOrEqual(1, $result->toolResults()->count());
        $this->assertTrue($result->response()->isSuccess());
        $this->assertNotSame('', $result->response()->message());
    }

    public function test_end_to_end_sales_summary(): void
    {
        $orchestrator = EndToEndAiOrchestrator::createDefault($this->bus);
        $result = $orchestrator->handle('كم مبيعات اليوم', 'tenant_demo', 'ar');

        $this->assertSame('SalesSummary', $result->plan()->intent());
        $this->assertStringContainsString('18', $result->response()->message());
    }

    public function test_end_to_end_english(): void
    {
        $orchestrator = EndToEndAiOrchestrator::createDefault($this->bus);
        $result = $orchestrator->handle('create customer', 'tenant_demo', 'en');

        $this->assertSame('en', $result->response()->locale());
        $this->assertTrue($result->response()->isSuccess());
    }

    public function test_policy_hides_stack_keys(): void
    {
        $policy = new ResponsePolicy();
        $filtered = $policy->filterPayload([
            'name' => 'Sara',
            'stack_trace' => 'secret',
            'password' => 'x',
        ]);
        $this->assertArrayHasKey('name', $filtered);
        $this->assertArrayNotHasKey('stack_trace', $filtered);
        $this->assertArrayNotHasKey('password', $filtered);
    }
}
