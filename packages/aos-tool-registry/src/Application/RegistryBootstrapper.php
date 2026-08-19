<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Domain\Approval\ApprovalDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Capability\CapabilityDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Intent\IntentDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Policy\PolicyDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ApprovalRequirement;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolCategory;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolMetadata;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolVersion;
use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;

/**
 * Seeds platform defaults + optional Customer/Reservation demo packs for tests/bootstrap.
 */
final class RegistryBootstrapper
{
    public function __construct(private readonly ToolRegistrar $registrar) {}

    public function bootstrapPlatformDefaults(): void
    {
        $this->registrar->registerProvider(new ProviderDescriptor(
            'aos.platform',
            'AOS Platform Registry',
            '0.16.0',
            ['platform'],
        ));

        $this->registrar->registerPolicy(new PolicyDescriptor(
            'default_read_policy',
            'Allow read tools without HITL',
            ['mode:assistant|hybrid|full_auto'],
        ));

        $this->registrar->registerPolicy(new PolicyDescriptor(
            'booking_write_policy',
            'Booking mutations require mode + capability checks',
            ['mode:hybrid|full_auto', 'approval:often'],
        ));

        $this->registrar->registerApproval(new ApprovalDescriptor(
            'none',
            ApprovalRequirement::None,
            'No approval required',
        ));
        $this->registrar->registerApproval(new ApprovalDescriptor(
            'often',
            ApprovalRequirement::Often,
            'Often requires human approval',
        ));
        $this->registrar->registerApproval(new ApprovalDescriptor(
            'always',
            ApprovalRequirement::Always,
            'Always requires human approval',
        ));
    }

    public function bootstrapCustomerPack(): void
    {
        $this->registrar->registerProvider(new ProviderDescriptor(
            'dressnmore.customer.binding',
            'Customer Domain Binding',
            '0.14.0',
            ['customer'],
        ));

        foreach ([
            ['Customer.Read', 'Read customer profile and related context', false],
            ['Customer.Write', 'Create/update customer profile', true],
            ['Customer.Search', 'Search customers', false],
        ] as [$name, $desc, $write]) {
            $this->registrar->registerCapability(new CapabilityDescriptor($name, 'customer', $desc, $write));
        }

        $this->registrar->registerTool($this->tool(
            'GetCustomer',
            'customer',
            'Resolve customer profile',
            ToolCategory::Customer,
            ['Customer.Read'],
            ToolRiskLevel::Low,
            ApprovalRequirement::None,
            ['customerRef', 'tenantId'],
            ['customer profile'],
            'dressnmore.customer.binding',
            'customer.read',
        ), validate: true);

        $this->registrar->registerTool($this->tool(
            'SearchCustomer',
            'customer',
            'Search customers',
            ToolCategory::Customer,
            ['Customer.Search'],
            ToolRiskLevel::Low,
            ApprovalRequirement::None,
            ['query', 'tenantId'],
            ['candidates[]'],
            'dressnmore.customer.binding',
            'customer.search',
        ));

        $this->registrar->registerTool($this->tool(
            'CreateCustomer',
            'customer',
            'Create customer',
            ToolCategory::Customer,
            ['Customer.Write'],
            ToolRiskLevel::Medium,
            ApprovalRequirement::Often,
            ['profile', 'tenantId'],
            ['customerRef'],
            'dressnmore.customer.binding',
            'customer.create',
        ));

        $this->registrar->registerIntent(new IntentDescriptor(
            'CreateCustomer',
            [
                ['tool' => 'SearchCustomer', 'capability' => 'Customer.Search'],
                ['tool' => 'CreateCustomer', 'capability' => 'Customer.Write'],
            ],
            ['Customer.Search', 'Customer.Write'],
            'default_read_policy',
            'often',
            'customer',
        ));
    }

    public function bootstrapReservationPack(): void
    {
        $this->registrar->registerProvider(new ProviderDescriptor(
            'dressnmore.reservation.binding',
            'Reservation Domain Binding',
            '0.15.0',
            ['reservation'],
        ));

        foreach ([
            ['Reservation.Read', 'Read reservations and availability', false],
            ['Reservation.Create', 'Create reservations', true],
            ['Reservation.Update', 'Update/reschedule/cancel reservations', true],
        ] as [$name, $desc, $write]) {
            $this->registrar->registerCapability(new CapabilityDescriptor($name, 'reservation', $desc, $write));
        }

        $this->registrar->registerTool($this->tool(
            'CheckAvailability',
            'reservation',
            'Check slot availability',
            ToolCategory::Reservation,
            ['Reservation.Read'],
            ToolRiskLevel::Low,
            ApprovalRequirement::None,
            ['serviceRef', 'date', 'time?'],
            ['available'],
            'dressnmore.reservation.binding',
            'reservation.availability.check',
        ));

        $this->registrar->registerTool($this->tool(
            'CreateReservation',
            'reservation',
            'Create reservation',
            ToolCategory::Reservation,
            ['Reservation.Create'],
            ToolRiskLevel::Medium,
            ApprovalRequirement::Often,
            ['customerRef', 'serviceRef', 'date', 'time'],
            ['reservationRef'],
            'dressnmore.reservation.binding',
            'reservation.create',
        ));

        $this->registrar->registerTool($this->tool(
            'CancelReservation',
            'reservation',
            'Cancel reservation',
            ToolCategory::Reservation,
            ['Reservation.Update'],
            ToolRiskLevel::Medium,
            ApprovalRequirement::Often,
            ['reservationRef', 'reason?'],
            ['cancelled'],
            'dressnmore.reservation.binding',
            'reservation.cancel',
        ));

        $this->registrar->registerIntent(new IntentDescriptor(
            'BookReservation',
            [
                ['tool' => 'CheckAvailability', 'capability' => 'Reservation.Read'],
                ['tool' => 'CreateReservation', 'capability' => 'Reservation.Create'],
            ],
            ['Reservation.Read', 'Reservation.Create'],
            'booking_write_policy',
            'often',
            'reservation',
        ));

        $this->registrar->registerIntent(new IntentDescriptor(
            'CancelReservation',
            [
                ['tool' => 'CancelReservation', 'capability' => 'Reservation.Update'],
            ],
            ['Reservation.Update'],
            'booking_write_policy',
            'often',
            'reservation',
        ));
    }

    /**
     * @param list<string> $capabilities
     * @param list<string> $inputs
     * @param list<string> $outputs
     */
    private function tool(
        string $name,
        string $owner,
        string $description,
        ToolCategory $category,
        array $capabilities,
        ToolRiskLevel $risk,
        ApprovalRequirement $approval,
        array $inputs,
        array $outputs,
        string $providerId,
        string $permission,
    ): ToolDescriptor {
        return new ToolDescriptor(
            new ToolMetadata(
                $name,
                ToolVersion::parse('1.0.0'),
                $owner,
                $description,
                $category,
                $capabilities,
                $risk,
                $approval,
                ['assistant', 'hybrid', 'full_auto'],
                $inputs,
                $outputs,
            ),
            $providerId,
            $permission,
        );
    }
}
