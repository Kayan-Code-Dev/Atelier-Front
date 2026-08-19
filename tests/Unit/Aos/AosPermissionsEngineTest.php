<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Permissions\Application\PermissionEngineFacade;
use DressnMore\Aos\Permissions\Architecture\PermissionsScopeDecision;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use Tests\TestCase;

final class AosPermissionsEngineTest extends TestCase
{
    public function test_permissions_module_is_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.permissions'));
    }

    public function test_facade_authorizes_seeded_capability(): void
    {
        /** @var PermissionEngineFacade $engine */
        $engine = $this->app->make(PermissionEngineFacade::class);

        $decision = $engine->authorizeCapability(
            'read_knowledge',
            'assistant',
            ['read_knowledge'],
            ['perm.read_knowledge'],
        );

        $this->assertSame(AuthorizationOutcome::Authorized, $decision->outcome());
    }

    public function test_sprint5_scope_excludes_integrations(): void
    {
        $excluded = PermissionsScopeDecision::excludedConcerns();
        $this->assertContains('openai', $excluded);
        $this->assertContains('planner', $excluded);
        $this->assertContains('database', $excluded);
        $this->assertSame(['dressnmore/aos-permissions'], PermissionsScopeDecision::includedPackages());
    }
}
