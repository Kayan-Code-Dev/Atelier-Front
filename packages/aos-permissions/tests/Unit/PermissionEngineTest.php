<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Tests\Unit;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Permissions\Application\AuthorizationManager;
use DressnMore\Aos\Permissions\Application\AuthorizationPipelineFactory;
use DressnMore\Aos\Permissions\Domain\Approval\ApprovalEngine;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityEngine;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityRegistry;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionEngine;
use DressnMore\Aos\Permissions\Domain\Factories\AuthorizationRequestFactory;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeManager;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionEngine;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionRegistry;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyDefinition;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyEngine;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyId;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyRegistry;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyResolver;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyType;
use DressnMore\Aos\Permissions\Domain\Risk\RiskEvaluator;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;
use DressnMore\Aos\Permissions\Infrastructure\Bootstrap\BuiltinCatalogBootstrap;
use DressnMore\Aos\Permissions\Infrastructure\Persistence\InMemoryApprovalRepository;
use PHPUnit\Framework\TestCase;

final class PermissionEngineTest extends TestCase
{
    private AuthorizationManager $manager;

    private AuthorizationRequestFactory $factory;

    private CapabilityRegistry $capabilities;

    private PolicyRegistry $policies;

    protected function setUp(): void
    {
        $this->capabilities = new CapabilityRegistry();
        $permissions = new PermissionRegistry();
        $this->policies = new PolicyRegistry();
        (new BuiltinCatalogBootstrap($this->capabilities, $permissions))->seed();

        $capabilityEngine = new CapabilityEngine($this->capabilities);
        $permissionEngine = new PermissionEngine($permissions);
        $modeManager = new OperatingModeManager();
        $policyEngine = new PolicyEngine($this->policies);
        $policyResolver = new PolicyResolver($this->policies);
        $riskEvaluator = new RiskEvaluator();
        $decisionEngine = new DecisionEngine($modeManager);
        $approvalEngine = new ApprovalEngine(new InMemoryApprovalRepository());

        $pipeline = (new AuthorizationPipelineFactory(
            $capabilityEngine,
            $permissionEngine,
            $modeManager,
            $policyEngine,
            $policyResolver,
            $riskEvaluator,
            $decisionEngine,
            $approvalEngine,
        ))->create();

        $bus = new class implements EventBusInterface {
            public function publish(object $event): void {}

            public function subscribe(string $event, string|callable $listener): void {}
        };

        $this->manager = new AuthorizationManager($pipeline, $approvalEngine, $bus);
        $this->factory = new AuthorizationRequestFactory();
    }

    public function test_capability_and_permission_resolution_authorizes_read(): void
    {
        $decision = $this->manager->authorize($this->factory->make(
            'read_customer',
            'assistant',
            ['read_customer'],
            ['perm.read_customer'],
        ));

        $this->assertSame(AuthorizationOutcome::Authorized, $decision->outcome());
    }

    public function test_missing_capability_denied(): void
    {
        $decision = $this->manager->authorize($this->factory->make(
            'read_customer',
            'assistant',
            [],
            ['perm.read_customer'],
        ));

        $this->assertSame(AuthorizationOutcome::Denied, $decision->outcome());
    }

    public function test_policy_evaluation_can_deny(): void
    {
        $this->policies->register(new PolicyDefinition(
            PolicyId::fromString('deny-cancel'),
            PolicyType::Security,
            'Deny cancel reservation',
            AuthorizationOutcome::Denied,
            10,
            null,
            ['cancel_reservation'],
            ['assistant'],
        ));

        $decision = $this->manager->authorize($this->factory->make(
            'cancel_reservation',
            'assistant',
            ['cancel_reservation'],
            ['perm.cancel_reservation'],
        ));

        $this->assertSame(AuthorizationOutcome::Denied, $decision->outcome());
    }

    public function test_risk_evaluation_requires_approval_for_high(): void
    {
        $decision = $this->manager->authorize($this->factory->make(
            'cancel_reservation',
            'hybrid',
            ['cancel_reservation'],
            ['perm.cancel_reservation'],
        ));

        $this->assertSame(AuthorizationOutcome::ApprovalRequired, $decision->outcome());
        $this->assertNotNull($decision->approvalRequestId());
        $this->assertSame(RiskLevel::High, $decision->riskLevel());
    }

    public function test_approval_flow_grant(): void
    {
        $pending = $this->manager->authorize($this->factory->make(
            'issue_invoice',
            'hybrid',
            ['issue_invoice'],
            ['perm.issue_invoice'],
        ));
        $this->assertSame(AuthorizationOutcome::ApprovalRequired, $pending->outcome());

        $granted = $this->manager->grantApproval(
            $pending->approvalRequestId(),
            'supervisor-1',
            'ok',
        );
        $this->assertSame(AuthorizationOutcome::Authorized, $granted->outcome());
    }

    public function test_operating_mode_human_only_escalates(): void
    {
        $decision = $this->manager->authorize($this->factory->make(
            'read_customer',
            'human_only',
            ['read_customer'],
            ['perm.read_customer'],
        ));

        $this->assertSame(AuthorizationOutcome::HumanEscalation, $decision->outcome());
    }

    public function test_read_only_blocks_mutations(): void
    {
        $decision = $this->manager->authorize($this->factory->make(
            'create_reservation',
            'read_only',
            ['create_reservation'],
            ['perm.create_reservation'],
        ));

        $this->assertSame(AuthorizationOutcome::Denied, $decision->outcome());
    }

    public function test_full_auto_critical_escalates_to_human(): void
    {
        $decision = $this->manager->authorize($this->factory->make(
            'issue_invoice',
            'full_auto',
            ['issue_invoice'],
            ['perm.issue_invoice'],
            [],
            RiskLevel::Critical,
        ));

        $this->assertSame(AuthorizationOutcome::HumanEscalation, $decision->outcome());
    }
}
