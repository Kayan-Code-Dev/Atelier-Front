<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Tests\Unit;

use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;
use DressnMore\CustomerBinding\Application\CustomerCapabilityProvider;
use DressnMore\CustomerBinding\Application\CustomerContextBuilder;
use DressnMore\CustomerBinding\Application\CustomerIntentMapper;
use DressnMore\CustomerBinding\Application\CustomerPolicyAdapter;
use DressnMore\CustomerBinding\Application\CustomerResolver;
use DressnMore\CustomerBinding\Application\CustomerSnapshotBuilder;
use DressnMore\CustomerBinding\Application\CustomerTimelineBuilder;
use DressnMore\CustomerBinding\Application\CustomerToolAdapter;
use DressnMore\CustomerBinding\Domain\Customer\CustomerId;
use DressnMore\CustomerBinding\Domain\Customer\CustomerReadModel;
use DressnMore\CustomerBinding\Domain\Timeline\TimelineSource;
use DressnMore\CustomerBinding\Domain\Tools\ApprovalPolicy;
use DressnMore\CustomerBinding\Domain\Tools\CustomerToolCatalog;
use DressnMore\CustomerBinding\Domain\Tools\CustomerToolName;
use DressnMore\CustomerBinding\Infrastructure\InMemory\InMemoryCustomerEventPublisher;
use DressnMore\CustomerBinding\Infrastructure\InMemory\InMemoryCustomerReadModelPort;
use PHPUnit\Framework\TestCase;

final class CustomerBindingTest extends TestCase
{
    private CustomerReadModel $customer;
    private InMemoryCustomerReadModelPort $port;
    private InMemoryCustomerEventPublisher $events;

    protected function setUp(): void
    {
        $this->customer = new CustomerReadModel(
            CustomerId::fromString('cus_1'),
            'tenant_demo',
            'Sara Alami',
            phone: '966500000001',
            preferredLanguage: 'ar',
            vip: true,
            tags: ['Bridal', 'VIP'],
            preferences: ['channel' => 'whatsapp'],
            measurements: ['bust' => 90],
            orders: [['id' => 'ord_1', 'status' => 'open', 'at' => '2026-08-01']],
            reservations: [['id' => 'res_1', 'status' => 'confirmed', 'at' => '2026-08-02']],
            invoices: [['id' => 'inv_1', 'status' => 'unpaid', 'at' => '2026-08-03']],
            paymentStatus: 'partial',
            notes: [['text' => 'Prefers evening fittings']],
            lastInteractionAt: '2026-08-05',
            aiSummaryPlaceholder: 'VIP bridal customer',
            timelineSeed: [
                ['source' => TimelineSource::WhatsApp->value, 'at' => '2026-08-05', 'title' => 'WhatsApp message'],
                ['source' => TimelineSource::Approvals->value, 'at' => '2026-08-04', 'title' => 'Discount approval'],
            ],
        );

        $this->port = new InMemoryCustomerReadModelPort();
        $this->port->seed($this->customer);
        $this->events = new InMemoryCustomerEventPublisher();
    }

    public function test_customer_resolution(): void
    {
        $resolver = new CustomerResolver($this->port, $this->events);
        $byId = $resolver->resolveById('tenant_demo', CustomerId::fromString('cus_1'));
        $byPhone = $resolver->resolveByPhone('tenant_demo', '966500000001');

        $this->assertNotNull($byId);
        $this->assertSame('Sara Alami', $byId->displayName());
        $this->assertNotNull($byPhone);
        $this->assertTrue($resolver->exists('tenant_demo', '966500000001'));
        $this->assertFalse($resolver->exists('tenant_demo', '000'));
        $this->assertNotEmpty($this->events->all());
    }

    public function test_context_building(): void
    {
        $context = (new CustomerContextBuilder($this->events))->build($this->customer);
        $this->assertTrue($context->vipStatus());
        $this->assertSame('ar', $context->preferredLanguage());
        $this->assertSame('partial', $context->paymentStatus());
        $this->assertArrayHasKey('name', $context->basicProfile());
        $this->assertNotEmpty($context->measurements());
        $this->assertSame('VIP bridal customer', $context->aiSummaryPlaceholder());
    }

    public function test_snapshot_building(): void
    {
        $snapshot = (new CustomerSnapshotBuilder($this->events))->build($this->customer);
        $this->assertSame(1, $snapshot->openOrders());
        $this->assertSame(1, $snapshot->openReservations());
        $this->assertSame(1, $snapshot->openInvoices());
        $this->assertTrue($snapshot->vip());
        $this->assertStringContainsString('Sara', (string) $snapshot->summary());
    }

    public function test_timeline_building(): void
    {
        $timeline = (new CustomerTimelineBuilder($this->events))->build($this->customer);
        $this->assertGreaterThanOrEqual(5, $timeline->count());
        $sources = array_map(static fn ($e) => $e->source()->value, $timeline->entries());
        $this->assertContains(TimelineSource::WhatsApp->value, $sources);
        $this->assertContains(TimelineSource::Reservations->value, $sources);
        $this->assertContains(TimelineSource::Invoices->value, $sources);
        $this->assertContains(TimelineSource::Orders->value, $sources);
        $this->assertContains(TimelineSource::Approvals->value, $sources);
    }

    public function test_business_tool_contracts(): void
    {
        $adapter = new CustomerToolAdapter();
        $this->assertCount(15, $adapter->contracts());
        $this->assertTrue($adapter->supports('GetCustomer'));
        $this->assertTrue($adapter->supports('MergeCustomers'));

        $merge = CustomerToolCatalog::get(CustomerToolName::MergeCustomers);
        $this->assertSame(ToolRiskLevel::Critical, $merge->riskLevel());
        $this->assertSame(ApprovalPolicy::Always, $merge->approvalPolicy());
        $this->assertContains('CustomerMerged', $merge->expectedEvents());

        $policy = new CustomerPolicyAdapter();
        $this->assertTrue($policy->requiresApproval('MergeCustomers'));
        $this->assertSame('critical', $policy->riskLevel('MergeCustomers'));
        $this->assertFalse($policy->requiresApproval('GetCustomer'));

        $caps = new CustomerCapabilityProvider();
        $this->assertTrue($caps->supports('read_customer_profile'));
        $this->assertTrue($caps->supports('merge_customers'));

        $mapper = new CustomerIntentMapper();
        $this->assertSame('GetCustomer', $mapper->map('customer_profile'));
        $this->assertSame('MergeCustomers', $mapper->map('merge_customers'));
        $this->assertNull($mapper->map('unknown_intent'));
    }
}
