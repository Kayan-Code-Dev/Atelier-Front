<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Infrastructure\Bootstrap;

use DressnMore\Aos\Permissions\Domain\Capability\BuiltinCapability;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityDefinition;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityRegistryInterface;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionCode;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionDefinition;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionRegistryInterface;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Seeds built-in capabilities and permissions (no tenant business rules).
 */
final class BuiltinCatalogBootstrap
{
    public function __construct(
        private readonly CapabilityRegistryInterface $capabilities,
        private readonly PermissionRegistryInterface $permissions,
    ) {}

    public function seed(): void
    {
        $map = [
            BuiltinCapability::ReadCustomer->value => [RiskLevel::Low, ['perm.read_customer'], false],
            BuiltinCapability::ReadInvoice->value => [RiskLevel::Low, ['perm.read_invoice'], false],
            BuiltinCapability::CreateReservation->value => [RiskLevel::Medium, ['perm.create_reservation'], false],
            BuiltinCapability::UpdateReservation->value => [RiskLevel::Medium, ['perm.update_reservation'], false],
            BuiltinCapability::CancelReservation->value => [RiskLevel::High, ['perm.cancel_reservation'], true],
            BuiltinCapability::IssueInvoice->value => [RiskLevel::High, ['perm.issue_invoice'], true],
            BuiltinCapability::ReadKnowledge->value => [RiskLevel::Low, ['perm.read_knowledge'], false],
            BuiltinCapability::CreateTask->value => [RiskLevel::Medium, ['perm.create_task'], false],
            BuiltinCapability::AssignTask->value => [RiskLevel::Medium, ['perm.assign_task'], false],
            BuiltinCapability::SendNotification->value => [RiskLevel::Medium, ['perm.send_notification'], false],
            BuiltinCapability::GenerateReport->value => [RiskLevel::Low, ['perm.generate_report'], false],
            BuiltinCapability::ExecuteAutomation->value => [RiskLevel::High, ['perm.execute_automation'], true],
            BuiltinCapability::TransferConversation->value => [RiskLevel::Medium, ['perm.transfer_conversation'], false],
            BuiltinCapability::ApproveRequest->value => [RiskLevel::High, ['perm.approve_request'], true],
        ];

        foreach ($map as $cap => [$risk, $perms, $approval]) {
            /** @var list<string> $perms */
            foreach ($perms as $perm) {
                if (! $this->permissions->has(PermissionCode::fromString($perm))) {
                    $this->permissions->register(new PermissionDefinition(
                        PermissionCode::fromString($perm),
                        'Auto-seeded permission for '.$cap,
                    ));
                }
            }

            $code = CapabilityCode::fromString($cap);
            if (! $this->capabilities->has($code)) {
                $this->capabilities->register(new CapabilityDefinition(
                    $code,
                    'Built-in capability '.$cap,
                    $risk,
                    $perms,
                    $approval,
                ));
            }
        }
    }
}
