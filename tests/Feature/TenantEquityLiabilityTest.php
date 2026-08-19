<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\Tenant\Account;
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

class TenantEquityLiabilityTest extends TestCase
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
        $this->user = $this->createUser([
            'accounting.view',
            'accounting.equity.view',
            'accounting.equity.create',
            'accounting.liabilities.view',
            'accounting.liabilities.create',
            'accounting.liabilities.settle',
        ]);
        Artisan::call('db:seed', ['--database' => 'tenant', '--class' => AccountSeeder::class, '--force' => true]);
    }

    public function test_owner_contribution_and_drawings_post_balanced_journals(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $cash = Account::query()->where('code', '1000')->firstOrFail();

        $contribution = $this->postJson('/api/tenant/accounting/equity', [
            'type' => 'contribution',
            'owner_name' => 'المالك',
            'amount' => 15000,
            'occurred_at' => '2026-03-01',
            'cash_account_id' => $cash->id,
        ], $this->headers())->assertCreated();

        $journal = JournalEntry::query()->findOrFail((int) $contribution->json('data.journal_entry_id'));
        $this->assertTrue((bool) $journal->is_balanced);
        $this->assertSame(JournalEntry::SOURCE_EQUITY, $journal->source_type);
        $this->assertEquals(15000, (float) $journal->total_debit);

        $drawing = $this->postJson('/api/tenant/accounting/equity', [
            'type' => 'drawing',
            'owner_name' => 'المالك',
            'amount' => 2000,
            'occurred_at' => '2026-03-15',
            'cash_account_id' => $cash->id,
        ], $this->headers())->assertCreated();

        $drawJournal = JournalEntry::query()->findOrFail((int) $drawing->json('data.journal_entry_id'));
        $this->assertTrue((bool) $drawJournal->is_balanced);
        $drawings = Account::query()->where('code', '3100')->firstOrFail();
        $this->assertNotNull($drawJournal->lines()->where('account_id', $drawings->id)->where('debit', 2000)->first());

        $summary = $this->getJson('/api/tenant/accounting/summary?period=year', $this->headers())->assertOk();
        $this->assertEquals(15000, $summary->json('data.capital'));
        $this->assertEquals(2000, $summary->json('data.owner_drawings'));
        $this->assertEquals(13000, $summary->json('data.cash_balance'));
    }

    public function test_loan_receipt_and_settlement_hit_ledger(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $cash = Account::query()->where('code', '1010')->firstOrFail();
        $loan = Account::query()->where('code', '2200')->firstOrFail();

        $created = $this->postJson('/api/tenant/accounting/liabilities', [
            'type' => 'loan',
            'lender' => 'البنك',
            'number' => 'LN-1',
            'name' => 'قرض تشغيلي',
            'principal' => 10000,
            'start_date' => '2026-04-01',
            'due_date' => '2026-12-01',
            'liability_account_id' => $loan->id,
            'cash_account_id' => $cash->id,
        ], $this->headers())->assertCreated();

        $id = (int) $created->json('data.id');
        $receipt = JournalEntry::query()->findOrFail((int) $created->json('data.receipt_journal_entry_id'));
        $this->assertTrue((bool) $receipt->is_balanced);
        $this->assertEquals(10000, (float) $receipt->total_debit);

        $this->postJson('/api/tenant/accounting/liabilities/'.$id.'/settle', [
            'amount' => 2500,
            'settled_at' => '2026-05-01',
            'cash_account_id' => $cash->id,
            'reference' => 'PAY-1',
        ], $this->headers())->assertOk()->assertJsonPath('data.outstanding', 7500);

        $summary = $this->getJson('/api/tenant/accounting/summary?date_to=2026-12-31', $this->headers())->assertOk();
        $this->assertEquals(7500, $summary->json('data.loans'));
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $this->centralDatabasePath = $testingPath.'/central-equity-lia.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-equity-lia.sqlite';
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
            'name' => 'Equity Tenant',
            'slug' => 'equity',
            'database_name' => $this->tenantDatabasePath,
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now()->subDay(),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(10),
        ]);
    }

    /**
     * @param  list<string>  $keys
     */
    private function createUser(array $keys): User
    {
        $role = Role::query()->create(['name' => 'Role '.uniqid(), 'slug' => 'role-'.uniqid()]);
        $role->permissions()->sync(Permission::query()->whereIn('key', $keys)->pluck('id')->all());
        $user = User::query()->create([
            'name' => 'Equity User',
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
