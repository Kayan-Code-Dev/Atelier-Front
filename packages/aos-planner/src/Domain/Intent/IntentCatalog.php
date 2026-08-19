<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Intent;

/**
 * Heuristic intent catalog — rule-based, no LLM.
 *
 * @phpstan-type IntentRule array{code: string, keywords: list<string>, conflict_with?: list<string>, write?: bool, tools?: list<string>, goal?: string, approval?: bool, risk?: string}
 */
final class IntentCatalog
{
    /**
     * @return list<IntentRule>
     */
    public function rules(): array
    {
        return [
            [
                'code' => 'check_balance',
                'keywords' => ['رصيد', 'متبقي', 'مبلغ علي', 'balance', 'outstanding'],
                'tools' => ['SearchCustomer', 'GetCustomerProfile', 'GetOutstandingBalance'],
                'goal' => 'resolve_outstanding_balance',
                'write' => false,
                'risk' => 'low',
            ],
            [
                'code' => 'book_fitting',
                'keywords' => ['احجز', 'أحجز', 'موعد', 'بروفة', 'fitting', 'reservation', 'حجز'],
                'tools' => ['FindAvailableSlots', 'CreateReservation'],
                'goal' => 'book_fitting_appointment',
                'write' => true,
                'approval' => true,
                'risk' => 'medium',
                'conflict_with' => ['cancel_reservation'],
            ],
            [
                'code' => 'cancel_reservation',
                'keywords' => ['الغاء', 'إلغاء', 'الغي', 'ألغي', 'cancel', 'الغي الموعد'],
                'tools' => ['CancelReservation'],
                'goal' => 'cancel_fitting_appointment',
                'write' => true,
                'approval' => true,
                'risk' => 'high',
                'conflict_with' => ['book_fitting'],
            ],
            [
                'code' => 'order_status',
                'keywords' => ['حالة الطلب', 'وين طلبي', 'order status', 'تتبع'],
                'tools' => ['GetOrderStatus', 'ListOpenOrdersForCustomer'],
                'goal' => 'check_order_status',
                'write' => false,
                'risk' => 'low',
            ],
            [
                'code' => 'ask_knowledge',
                'keywords' => ['سياسة', 'ساعات', 'faq', 'كيف', 'هل يمكن', 'policy', 'hours'],
                'tools' => ['SearchKnowledge', 'SearchFAQ', 'GetBusinessHours'],
                'goal' => 'answer_from_knowledge',
                'write' => false,
                'risk' => 'low',
            ],
            [
                'code' => 'transfer_human',
                'keywords' => ['موظف', 'بشري', 'human', 'تحدث مع', 'خدمة العملاء'],
                'tools' => ['TransferConversation'],
                'goal' => 'escalate_to_human',
                'write' => true,
                'risk' => 'medium',
            ],
        ];
    }
}
