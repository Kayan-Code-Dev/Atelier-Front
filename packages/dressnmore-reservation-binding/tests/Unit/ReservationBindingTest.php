<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Tests\Unit;

use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;
use DressnMore\ReservationBinding\Application\ReservationAvailabilityResolver;
use DressnMore\ReservationBinding\Application\ReservationCapabilityProvider;
use DressnMore\ReservationBinding\Application\ReservationContextBuilder;
use DressnMore\ReservationBinding\Application\ReservationIntentMapper;
use DressnMore\ReservationBinding\Application\ReservationPolicyAdapter;
use DressnMore\ReservationBinding\Application\ReservationReminderBuilder;
use DressnMore\ReservationBinding\Application\ReservationSnapshotBuilder;
use DressnMore\ReservationBinding\Application\ReservationTimelineBuilder;
use DressnMore\ReservationBinding\Application\ReservationToolAdapter;
use DressnMore\ReservationBinding\Domain\Availability\AvailabilitySlot;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationId;
use DressnMore\ReservationBinding\Domain\Reservation\ReservationReadModel;
use DressnMore\ReservationBinding\Domain\Timeline\TimelineKind;
use DressnMore\ReservationBinding\Domain\Tools\ApprovalPolicy;
use DressnMore\ReservationBinding\Domain\Tools\ReservationToolCatalog;
use DressnMore\ReservationBinding\Domain\Tools\ReservationToolName;
use DressnMore\ReservationBinding\Infrastructure\InMemory\InMemoryReservationAvailabilityPort;
use DressnMore\ReservationBinding\Infrastructure\InMemory\InMemoryReservationEventPublisher;
use PHPUnit\Framework\TestCase;

final class ReservationBindingTest extends TestCase
{
    private ReservationReadModel $reservation;
    private InMemoryReservationEventPublisher $events;

    protected function setUp(): void
    {
        $this->reservation = new ReservationReadModel(
            ReservationId::fromString('res_1'),
            'tenant_demo',
            'cus_1',
            'Sara Alami',
            'svc_fitting',
            'Bridal Fitting',
            '2026-08-10',
            '16:00',
            assignedEmployeeRef: 'emp_1',
            assignedEmployeeName: 'Layla',
            status: 'confirmed',
            notes: [['text' => 'Bring veil sample', 'at' => '2026-08-01']],
            history: [
                ['type' => 'reschedule', 'at' => '2026-08-02', 'detail' => 'Moved from 15:00'],
                ['type' => 'arrival', 'at' => '2026-08-10T16:05'],
            ],
            reminders: [
                ['channel' => 'whatsapp', 'at' => '2026-08-10T09:00', 'status' => 'scheduled'],
            ],
            timelineSeed: [
                ['kind' => TimelineKind::Completion->value, 'at' => '2026-08-10T17:00', 'title' => 'Fitting completed'],
            ],
        );
        $this->events = new InMemoryReservationEventPublisher();
    }

    public function test_availability_resolution(): void
    {
        $port = new InMemoryReservationAvailabilityPort();
        $port->seedSlot(new AvailabilitySlot('2026-08-10', '16:00', 'svc_fitting', 'emp_1'));
        $port->seedSlot(new AvailabilitySlot('2026-08-10', '17:00', 'svc_fitting', 'emp_1'));
        $port->block('tenant_demo', 'svc_fitting', '2026-08-10', '16:00');

        $resolver = new ReservationAvailabilityResolver($port);
        $blocked = $resolver->resolve('tenant_demo', 'svc_fitting', '2026-08-10', '16:00');
        $open = $resolver->resolve('tenant_demo', 'svc_fitting', '2026-08-10', '17:00');
        $slots = $resolver->availableSlots('tenant_demo', 'svc_fitting', '2026-08-10');

        $this->assertFalse($blocked->available());
        $this->assertTrue($open->available());
        $this->assertCount(1, $slots);
        $this->assertSame('17:00', $slots[0]->time());
    }

    public function test_context_building(): void
    {
        $context = (new ReservationContextBuilder($this->events))->build($this->reservation);
        $this->assertSame('confirmed', $context->status());
        $this->assertSame('Sara Alami', $context->customer()['name']);
        $this->assertSame('Bridal Fitting', $context->service()['name']);
        $this->assertSame('Layla', $context->assignedEmployee()['name']);
        $this->assertNotEmpty($context->notes());
        $this->assertNotEmpty($context->history());
        $this->assertNotEmpty($context->reminders());
        $this->assertNotEmpty($this->events->all());
    }

    public function test_snapshot_building(): void
    {
        $snapshot = (new ReservationSnapshotBuilder($this->events))->build($this->reservation);
        $this->assertSame('confirmed', $snapshot->status());
        $this->assertSame(1, $snapshot->reminderCount());
        $this->assertStringContainsString('Sara', (string) $snapshot->summary());
        $this->assertSame('Bridal Fitting', $snapshot->serviceName());
    }

    public function test_timeline_building(): void
    {
        $timeline = (new ReservationTimelineBuilder())->build($this->reservation);
        $this->assertGreaterThanOrEqual(4, $timeline->count());
        $kinds = array_map(static fn ($e) => $e->kind()->value, $timeline->entries());
        $this->assertContains(TimelineKind::Creation->value, $kinds);
        $this->assertContains(TimelineKind::Reschedule->value, $kinds);
        $this->assertContains(TimelineKind::Reminder->value, $kinds);
        $this->assertContains(TimelineKind::Arrival->value, $kinds);
        $this->assertContains(TimelineKind::Completion->value, $kinds);
    }

    public function test_business_tool_contracts(): void
    {
        $adapter = new ReservationToolAdapter();
        $this->assertCount(12, $adapter->contracts());
        $this->assertTrue($adapter->supports('CheckAvailability'));
        $this->assertTrue($adapter->supports("GetToday'sReservations"));
        $this->assertTrue($adapter->supports('CancelReservation'));

        $cancel = ReservationToolCatalog::get(ReservationToolName::CancelReservation);
        $this->assertSame(ToolRiskLevel::Medium, $cancel->riskLevel());
        $this->assertSame(ApprovalPolicy::Often, $cancel->approvalPolicy());
        $this->assertContains('ReservationCancelled', $cancel->expectedEvents());
        $this->assertNotEmpty($cancel->capabilities());

        $policy = new ReservationPolicyAdapter();
        $this->assertTrue($policy->requiresApproval('CreateReservation'));
        $this->assertFalse($policy->requiresApproval('GetReservation'));

        $caps = new ReservationCapabilityProvider();
        $this->assertTrue($caps->supports('create_booking'));
        $this->assertTrue($caps->supports('check_availability'));

        $mapper = new ReservationIntentMapper();
        $this->assertSame('CheckAvailability', $mapper->map('check_availability'));
        $this->assertSame('CreateReservation', $mapper->map('book'));
        $this->assertSame("GetToday'sReservations", $mapper->map('today_reservations'));
        $this->assertNull($mapper->map('unknown_intent'));

        $reminders = (new ReservationReminderBuilder($this->events))->build($this->reservation);
        $this->assertGreaterThanOrEqual(1, $reminders->count());
    }
}
