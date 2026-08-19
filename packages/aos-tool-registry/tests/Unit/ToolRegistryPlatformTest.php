<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Tests\Unit;

use DressnMore\Aos\ToolRegistry\Application\CapabilityValidator;
use DressnMore\Aos\ToolRegistry\Application\RegistryBootstrapper;
use DressnMore\Aos\ToolRegistry\Application\RegistryExporter;
use DressnMore\Aos\ToolRegistry\Application\RegistrySnapshotBuilder;
use DressnMore\Aos\ToolRegistry\Application\ApprovalRegistry;
use DressnMore\Aos\ToolRegistry\Application\CapabilityRegistry;
use DressnMore\Aos\ToolRegistry\Application\IntentRegistry;
use DressnMore\Aos\ToolRegistry\Application\IntentResolver;
use DressnMore\Aos\ToolRegistry\Application\PolicyRegistry;
use DressnMore\Aos\ToolRegistry\Application\ProviderRegistry;
use DressnMore\Aos\ToolRegistry\Application\ToolDiscovery;
use DressnMore\Aos\ToolRegistry\Application\ToolMetadataRegistry;
use DressnMore\Aos\ToolRegistry\Application\ToolRegistrar;
use DressnMore\Aos\ToolRegistry\Application\ToolRegistry;
use DressnMore\Aos\ToolRegistry\Application\ToolResolver;
use DressnMore\Aos\ToolRegistry\Application\ToolValidator;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolVersion;
use DressnMore\Aos\ToolRegistry\Infrastructure\InMemory\InMemoryRegistryEventPublisher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ToolRegistryPlatformTest extends TestCase
{
    private ToolRegistrar $registrar;
    private ToolRegistry $tools;
    private CapabilityRegistry $capabilities;
    private IntentRegistry $intents;
    private ToolDiscovery $discovery;
    private ToolResolver $resolver;
    private CapabilityValidator $capabilityValidator;
    private RegistrySnapshotBuilder $snapshots;
    private RegistryExporter $exporter;
    private InMemoryRegistryEventPublisher $events;

    protected function setUp(): void
    {
        $this->tools = new ToolRegistry();
        $this->capabilities = new CapabilityRegistry();
        $this->intents = new IntentRegistry();
        $policies = new PolicyRegistry();
        $approvals = new ApprovalRegistry();
        $providers = new ProviderRegistry();
        $metadata = new ToolMetadataRegistry();
        $this->events = new InMemoryRegistryEventPublisher();
        $validator = new ToolValidator($this->capabilities);
        $this->registrar = new ToolRegistrar(
            $this->tools,
            $metadata,
            $this->capabilities,
            $this->intents,
            $policies,
            $approvals,
            $providers,
            $validator,
            $this->events,
        );
        $this->discovery = new ToolDiscovery($this->tools);
        $this->resolver = new ToolResolver($this->discovery, $this->events);
        $this->capabilityValidator = new CapabilityValidator($this->capabilities, $this->events);
        $this->snapshots = new RegistrySnapshotBuilder($this->tools, $this->capabilities, $this->intents, $providers);
        $this->exporter = new RegistryExporter($this->snapshots);

        $boot = new RegistryBootstrapper($this->registrar);
        $boot->bootstrapPlatformDefaults();
        $boot->bootstrapCustomerPack();
        $boot->bootstrapReservationPack();
    }

    public function test_tool_registration(): void
    {
        $this->assertTrue($this->tools->has('GetCustomer'));
        $this->assertTrue($this->tools->has('CreateReservation'));
        $this->assertGreaterThanOrEqual(6, count($this->tools->all()));
        $this->assertNotEmpty($this->events->all());
    }

    public function test_capability_registration(): void
    {
        $this->assertTrue($this->capabilities->has('Customer.Read'));
        $this->assertTrue($this->capabilities->has('Reservation.Create'));
        $this->assertNotEmpty($this->capabilities->byOwnerDomain('customer'));
    }

    public function test_intent_registration(): void
    {
        $this->assertTrue($this->intents->has('BookReservation'));
        $this->assertTrue($this->intents->has('CreateCustomer'));
        $intent = (new IntentResolver($this->intents))->resolve('BookReservation');
        $this->assertSame(['CheckAvailability', 'CreateReservation'], $intent->toolNames());
    }

    public function test_tool_discovery(): void
    {
        $customerTools = $this->discovery->discover(category: 'customer');
        $names = array_map(static fn ($d) => $d->name(), $customerTools);
        $this->assertContains('GetCustomer', $names);
        $this->assertContains('CreateCustomer', $names);

        $byCap = $this->discovery->byCapability('Reservation.Read');
        $this->assertNotEmpty($byCap);
    }

    public function test_tool_resolution(): void
    {
        $descriptor = $this->resolver->resolve('CheckAvailability');
        $this->assertSame('CheckAvailability', $descriptor->name());
        $this->assertSame('reservation', $descriptor->metadata()->ownerDomain());
    }

    public function test_unregistered_tool_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->resolver->resolve('TotallyUnknownTool');
    }

    public function test_missing_capability_is_denied(): void
    {
        $this->expectException(RuntimeException::class);
        $this->capabilityValidator->assertGranted(
            ['Reservation.Create'],
            ['Customer.Read'],
        );
    }

    public function test_incompatible_version_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->resolver->resolve('GetCustomer', ToolVersion::parse('2.0.0'));
    }

    public function test_registry_snapshot(): void
    {
        $snapshot = $this->snapshots->build();
        $this->assertGreaterThan(0, $snapshot->toolCount());
        $this->assertGreaterThan(0, $snapshot->capabilityCount());
        $this->assertGreaterThan(0, $snapshot->intentCount());
        $this->assertArrayHasKey('tools', $snapshot->toArray());
    }

    public function test_registry_export(): void
    {
        $export = $this->exporter->export();
        $this->assertSame('aos-tool-registry.conceptual.v1', $export['format']);
        $this->assertContains('customer', $export['categories']);
        $this->assertContains('reservation', $export['categories']);
    }

    public function test_validation_requires_registered_capabilities(): void
    {
        $validator = new ToolValidator($this->capabilities);
        $descriptor = $this->tools->get('GetCustomer');
        $this->assertNotNull($descriptor);
        $this->assertSame([], $validator->validate($descriptor));
    }
}
