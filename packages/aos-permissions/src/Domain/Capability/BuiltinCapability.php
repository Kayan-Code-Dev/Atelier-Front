<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Capability;

/**
 * Built-in capability keys (extensible via CapabilityCode::custom).
 */
enum BuiltinCapability: string
{
    case ReadCustomer = 'read_customer';
    case ReadInvoice = 'read_invoice';
    case CreateReservation = 'create_reservation';
    case UpdateReservation = 'update_reservation';
    case CancelReservation = 'cancel_reservation';
    case IssueInvoice = 'issue_invoice';
    case ReadKnowledge = 'read_knowledge';
    case CreateTask = 'create_task';
    case AssignTask = 'assign_task';
    case SendNotification = 'send_notification';
    case GenerateReport = 'generate_report';
    case ExecuteAutomation = 'execute_automation';
    case TransferConversation = 'transfer_conversation';
    case ApproveRequest = 'approve_request';
}
