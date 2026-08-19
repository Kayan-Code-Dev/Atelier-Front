<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\Tenant\Account;
use App\Models\Tenant\AccountingAuditLog;
use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\AccountSeeder;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantAccountingSprint4Test extends TestCase
{
    private string $centralDatabasePath;

    private string $tenantDatabasePath;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSqliteDatabases();
        $this->runMigrations();
        Artisan::call('db:seed', ['--database' => 'tenant', '--class' => TenantRolePermissionSeeder::class, '--force' => true]);
        $this->tenant = $this->createTenant();
        $this->user = $this->createTenantUserWithPermissions([
            'accounting.view',
            'accounting.reports.view',
            'accounting.reports.export',
            'accounting.periods.view',
            'accounting.periods.close',
            'accounting.periods.reopen',
            'accounting.controls.view',
            'accounting.journal_entries.view',
            'accounting.journal_entries.create',
            'accounting.journal_entries.update',
            'accounting.journal_entries.approve',
        ]);
        Artisan::call('db:seed', ['--database' => 'tenant', '--class' => AccountSeeder::class, '--force' => true]);
    }

    public function test_income_statement_is_posted_ledger_projection_with_groups(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-10', '1000', 8000, '4100', 8000);
        $this->postJournal('2026-08-12', '5000', 1500, '1000', 1500);
        $this->postJournal('2026-08-13', '5200', 500, '1000', 500);

        $response = $this->getJson(
            '/api/tenant/accounting/reports/income-statement?date_from=2026-08-01&date_to=2026-08-31',
            $this->headers()
        )->assertOk();

        $this->assertEquals(8000, $response->json('data.gross_revenue'));
        $this->assertEquals(8000, $response->json('data.net_revenue'));
        $this->assertEquals(8000, $response->json('data.gross_profit'));
        $this->assertEquals(6000, $response->json('data.net_profit'));
        $this->assertEquals(6000, $response->json('data.net_income'));
        $groups = collect($response->json('data.expense_groups'));
        $this->assertEquals(1500, $groups->firstWhere('key', 'operating')['total']);
        $this->assertEquals(500, $groups->firstWhere('key', 'salaries')['total']);
        $this->assertNotEmpty($response->json('data.revenues.items.0.drill.path'));
    }

    public function test_balance_sheet_equation_and_structure(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-01', '1000', 50000, '3000', 50000);
        $this->postJournal('2026-08-15', '1200', 2000, '4100', 2000);

        $response = $this->getJson(
            '/api/tenant/accounting/reports/balance-sheet?date=2026-08-31',
            $this->headers()
        )->assertOk();

        $this->assertTrue($response->json('data.equation.balanced'));
        $this->assertEquals(
            $response->json('data.total_assets'),
            $response->json('data.liabilities_and_equity')
        );
        $this->assertEquals(52000, $response->json('data.total_assets'));
        $this->assertEquals('الميزانية متزنة', $response->json('data.equation.message'));
        $this->assertSame('النقدية', $response->json('data.structure.assets.current.groups.0.label'));
        $this->assertSame('العملاء', $response->json('data.structure.assets.current.groups.1.label'));
    }

    public function test_trial_balance_totals_match_and_hide_zeros(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-01', '1000', 70000, '3000', 70000);

        $response = $this->getJson(
            '/api/tenant/accounting/reports/trial-balance?date_from=2026-08-01&date_to=2026-08-31',
            $this->headers()
        )->assertOk();

        $this->assertTrue($response->json('data.balanced'));
        $this->assertEquals($response->json('data.total_debit'), $response->json('data.total_credit'));
        $this->assertEquals(70000, $response->json('data.total_debit'));
        $codes = collect($response->json('data.lines'))->pluck('code');
        $this->assertTrue($codes->contains('1000'));
        $this->assertTrue($codes->contains('3000'));
        $this->assertFalse($codes->contains('5200'));
    }

    public function test_cash_flow_opening_plus_movement_equals_closing(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-07-31', '1000', 10000, '3000', 10000);
        $this->postJournal('2026-08-10', '1000', 4000, '4100', 4000);

        $response = $this->getJson(
            '/api/tenant/accounting/reports/cash-flow?date_from=2026-08-01&date_to=2026-08-31',
            $this->headers()
        )->assertOk();

        $this->assertEquals(10000, $response->json('data.opening_cash'));
        $this->assertEquals(14000, $response->json('data.closing_cash'));
        $this->assertEquals(
            (float) $response->json('data.closing_cash'),
            (float) $response->json('data.opening_cash') + (float) $response->json('data.gl_cash_movement')
        );
        $this->assertArrayHasKey('treasury', $response->json('data'));
        $this->assertArrayHasKey('ok', $response->json('data.treasury'));
    }

    public function test_comparative_income_statement_includes_variance(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-07-10', '1000', 1000, '4100', 1000);
        $this->postJournal('2026-08-10', '1000', 2500, '4100', 2500);

        $response = $this->getJson(
            '/api/tenant/accounting/reports/income-statement?date_from=2026-08-01&date_to=2026-08-31&compare=1',
            $this->headers()
        )->assertOk();

        $this->assertEquals(2500, $response->json('data.net_profit'));
        $this->assertEquals(1000, $response->json('data.compare.net_profit'));
        $this->assertEquals(1500, $response->json('data.variance.net_profit.difference'));
    }

    public function test_close_period_is_control_boundary_and_does_not_mutate_ledger(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $entryId = $this->postJournal('2026-08-10', '1000', 500, '3000', 500);
        $this->getJson('/api/tenant/accounting/periods?year=2026', $this->headers())->assertOk();
        $period = AccountingPeriod::query()->where('year', 2026)->where('month', 8)->firstOrFail();

        $this->postJson('/api/tenant/accounting/periods/'.$period->id.'/close', [
            'confirm' => true,
        ], $this->headers())->assertOk()->assertJsonPath('data.status', 'closed');

        $this->assertSame(500.0, (float) JournalEntry::query()->findOrFail($entryId)->total_debit);
        $this->assertSame('posted', JournalEntry::query()->findOrFail($entryId)->status);

        $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-08-20',
            'description' => 'should fail',
            'lines' => [
                ['account_id' => $this->id('1000'), 'debit' => 10, 'credit' => 0],
                ['account_id' => $this->id('3000'), 'credit' => 10, 'debit' => 0],
            ],
        ], $this->headers())->assertStatus(422);

        $correction = $this->postJournal('2026-09-05', '3000', 10, '1000', 10);
        $this->assertSame('posted', JournalEntry::query()->findOrFail($correction)->status);
        $this->assertSame(500.0, (float) JournalEntry::query()->findOrFail($entryId)->total_debit);
    }

    public function test_reopen_requires_reason_and_writes_audit_trail(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-10', '1000', 100, '3000', 100);
        $this->getJson('/api/tenant/accounting/periods?year=2026', $this->headers())->assertOk();
        $period = AccountingPeriod::query()->where('year', 2026)->where('month', 8)->firstOrFail();
        $this->postJson('/api/tenant/accounting/periods/'.$period->id.'/close', ['confirm' => true], $this->headers())->assertOk();

        $this->postJson('/api/tenant/accounting/periods/'.$period->id.'/reopen', [
            'reason' => 'تصحيح قيد رواتب أغسطس',
        ], $this->headers())->assertOk()->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('accounting_audit_logs', [
            'action' => 'period_reopened',
            'entity_type' => 'accounting_period',
            'entity_id' => $period->id,
        ], 'tenant');

        $log = AccountingAuditLog::query()->where('action', 'period_reopened')->firstOrFail();
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertStringContainsString('تصحيح', (string) ($log->metadata['reason'] ?? ''));
    }

    public function test_control_center_and_unposted_view(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-10', '1000', 200, '3000', 200);
        $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-08-18',
            'description' => 'مسودة',
            'lines' => [
                ['account_id' => $this->id('1000'), 'debit' => 30, 'credit' => 0],
                ['account_id' => $this->id('3000'), 'credit' => 30, 'debit' => 0],
            ],
        ], $this->headers())->assertCreated();

        $controls = $this->getJson('/api/tenant/accounting/controls', $this->headers())->assertOk();
        $this->assertNotEmpty($controls->json('data.checks'));
        $this->assertGreaterThan(0, $controls->json('data.unposted.total'));

        $unposted = $this->getJson('/api/tenant/accounting/unposted', $this->headers())->assertOk();
        $this->assertGreaterThanOrEqual(1, $unposted->json('data.counts.draft_journal_entries'));

        $this->getJson('/api/tenant/accounting/exceptions', $this->headers())->assertOk();
        $this->getJson('/api/tenant/accounting/summary?period=month', $this->headers())
            ->assertOk()
            ->assertJsonStructure(['data' => ['financial_position', 'performance', 'liquidity', 'controls']]);
    }

    public function test_report_export_and_permissions(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-10', '1000', 50, '3000', 50);

        $this->get('/api/tenant/accounting/reports/trial-balance/export?format=csv&date_from=2026-08-01&date_to=2026-08-31', $this->headers())
            ->assertOk();
        $this->get('/api/tenant/accounting/reports/income-statement/export?format=csv&date_from=2026-08-01&date_to=2026-08-31', $this->headers())
            ->assertOk();
        $this->get('/api/tenant/accounting/reports/balance-sheet/export?format=csv&date=2026-08-31', $this->headers())
            ->assertOk();
        $this->get('/api/tenant/accounting/reports/cash-flow/export?format=csv&date_from=2026-08-01&date_to=2026-08-31', $this->headers())
            ->assertOk();

        $limited = $this->createTenantUserWithPermissions(['accounting.view']);
        Sanctum::actingAs($limited, ['*']);
        $this->postJson('/api/tenant/accounting/periods/1/reopen', [
            'reason' => 'محاولة غير مصرح بها',
        ], $this->headers())->assertStatus(403);
    }

    public function test_phase4_accounts_seeded(): void
    {
        foreach (AccountSeeder::PHASE4_ACCOUNT_CODES as $code) {
            $this->assertTrue(Account::query()->where('code', $code)->exists(), $code);
        }
    }

    private function postJournal(string $date, string $debitCode, float $amount, string $creditCode, float $creditAmount): int
    {
        $create = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => $date,
            'description' => 'Sprint4 '.$date,
            'lines' => [
                ['account_id' => $this->id($debitCode), 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->id($creditCode), 'credit' => $creditAmount, 'debit' => 0],
            ],
        ], $this->headers())->assertCreated();

        $id = (int) $create->json('data.id');
        $this->postJson("/api/tenant/accounting/journal-entries/{$id}/approve", [], $this->headers())->assertOk();

        return $id;
    }

    private function id(string $code): int
    {
        return (int) Account::query()->where('code', $code)->value('id');
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $this->centralDatabasePath = $testingPath.'/central-sprint4.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-sprint4.sqlite';
        @unlink($this->centralDatabasePath);
        @unlink($this->tenantDatabasePath);
        touch($this->centralDatabasePath);
        touch($this->tenantDatabasePath);
        Config::set('database.default', 'central');
        Config::set('database.connections.central', [
            'driver' => 'sqlite', 'database' => $this->centralDatabasePath, 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite', 'database' => $this->tenantDatabasePath, 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        DB::purge('central');
        DB::purge('tenant');
    }

    private function runMigrations(): void
    {
        Artisan::call('migrate:fresh', ['--database' => 'central', '--force' => true]);
        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => base_path('database/migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Sprint4 Tenant',
            'slug' => 'sprint4',
            'database_name' => $this->tenantDatabasePath,
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now()->subDay(),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(10),
        ]);
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function createTenantUserWithPermissions(array $permissionKeys): User
    {
        $role = Role::query()->create(['name' => 'Role '.uniqid(), 'slug' => 'role-'.uniqid()]);
        $role->permissions()->sync(Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all());
        $user = User::query()->create([
            'name' => 'Sprint4 User',
            'email' => uniqid().'@tenant.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return ['Accept' => 'application/json', 'X-Tenant' => $this->tenant->slug];
    }
}
