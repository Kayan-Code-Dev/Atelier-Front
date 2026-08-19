<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

/**
 * Registry-oriented intent catalog for Sprint 18 (no LLM).
 *
 * @phpstan-type IntentRule array{
 *   intent: string,
 *   keywords: list<string>,
 *   toolPlan: list<string>,
 *   capabilities: list<string>,
 *   goal: string,
 *   policy?: string,
 *   approval?: string,
 *   conflicts?: list<string>
 * }
 */
final class PlatformIntentCatalog
{
    /**
     * @return list<IntentRule>
     */
    public function rules(): array
    {
        return [
            [
                'intent' => 'BookReservation',
                'keywords' => ['احجز', 'أحجز', 'حجز', 'موعد', 'بروفة', 'book', 'reservation'],
                'toolPlan' => ['CheckAvailability', 'CreateReservation'],
                'capabilities' => ['Reservation.Read', 'Reservation.Create'],
                'goal' => 'Create Reservation',
                'policy' => 'booking_write_policy',
                'approval' => 'often',
                'conflicts' => ['CancelReservation'],
            ],
            [
                'intent' => 'CancelReservation',
                'keywords' => ['الغاء', 'إلغاء', 'الغي', 'ألغي', 'cancel'],
                'toolPlan' => ['CancelReservation'],
                'capabilities' => ['Reservation.Update'],
                'goal' => 'Cancel Reservation',
                'policy' => 'booking_write_policy',
                'approval' => 'often',
                'conflicts' => ['BookReservation'],
            ],
            [
                'intent' => 'CreateCustomer',
                'keywords' => ['أضف عميل', 'اضف عميل', 'عميل جديد', 'create customer', 'new customer'],
                'toolPlan' => ['SearchCustomer', 'CreateCustomer'],
                'capabilities' => ['Customer.Search', 'Customer.Write'],
                'goal' => 'Create Customer',
                'policy' => 'default_read_policy',
                'approval' => 'often',
            ],
            [
                'intent' => 'SalesSummary',
                'keywords' => ['مبيعات اليوم', 'كم مبيعات', 'sales summary', 'sales today'],
                'toolPlan' => ['GenerateReport'],
                'capabilities' => ['Reports.Read'],
                'goal' => 'Sales Summary',
                'policy' => 'default_read_policy',
                'approval' => 'none',
            ],
        ];
    }
}
