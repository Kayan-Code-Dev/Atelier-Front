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

class TenantJournalEntryTest extends TestCase
{
    private string $centralDatabasePath;

    private string $tenantDatabasePath;

    private Tenant $tenant;

    private User $user;

    /** @var array<int, int> */
    private array $accountIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSqliteDatabases();
        $this->runMigrations();
        $this->seedTenantPermissions();
        $this->tenant = $this->createTenant();
        $this->user = $this->createTenantUserWithPermissions([
            'accounting.journal_entries.view',
            'accounting.journal_entries.create',
            'accounting.journal_entries.update',
            'accounting.journal_entries.approve',
            'accounting.journal_entries.cancel',
            'accounting.journal_entries.reverse',
            'accounting.journal_entries.export',
            'accounting.view',
        ]);
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => AccountSeeder::class,
            '--force' => true,
        ]);
        $this->accountIds = Account::query()->where('allow_posting', true)->orderBy('code')->pluck('id')->all();
    }

    public function test_can_save_draft_unbalanced_journal_entry(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'description' => 'Draft unbalanced entry',
            'lines' => [
                ['account_id' => $this->accountIds[0], 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountIds[1], 'credit' => 50, 'debit' => 0],
            ],
        ], $this->tenantHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.is_balanced', false);
    }

    public function test_cannot_approve_unbalanced_journal_entry(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $create = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'description' => 'Unbalanced draft',
            'lines' => [
                ['account_id' => $this->accountIds[0], 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountIds[1], 'credit' => 50, 'debit' => 0],
            ],
        ], $this->tenantHeaders())->assertCreated();

        $entryId = (int) $create->json('data.id');

        $this->postJson("/api/tenant/accounting/journal-entries/{$entryId}/approve", [], $this->tenantHeaders())
            ->assertStatus(422);
    }

    public function test_approved_balanced_entry_succeeds(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $create = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'description' => 'Balanced draft',
            'lines' => [
                ['account_id' => $this->accountIds[0], 'debit' => 250, 'credit' => 0],
                ['account_id' => $this->accountIds[1], 'credit' => 250, 'debit' => 0],
            ],
        ], $this->tenantHeaders())->assertCreated();

        $entryId = (int) $create->json('data.id');

        $this->postJson("/api/tenant/accounting/journal-entries/{$entryId}/approve", [], $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'posted')
            ->assertJsonPath('data.is_balanced', true);
    }

    public function test_approved_entry_cannot_be_edited(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $entryId = $this->createApprovedEntry();

        $this->putJson("/api/tenant/accounting/journal-entries/{$entryId}", [
            'description' => 'Should fail',
        ], $this->tenantHeaders())->assertStatus(422);
    }

    public function test_cancelled_draft_cannot_be_edited_or_approved(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $create = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'description' => 'Draft to cancel',
            'lines' => [
                ['account_id' => $this->accountIds[0], 'debit' => 250, 'credit' => 0],
                ['account_id' => $this->accountIds[1], 'credit' => 250, 'debit' => 0],
            ],
        ], $this->tenantHeaders())->assertCreated();

        $entryId = (int) $create->json('data.id');

        $this->postJson("/api/tenant/accounting/journal-entries/{$entryId}/cancel", [
            'cancellation_reason' => 'Wrong posting',
        ], $this->tenantHeaders())->assertOk();

        $this->putJson("/api/tenant/accounting/journal-entries/{$entryId}", [
            'description' => 'Should fail',
        ], $this->tenantHeaders())->assertStatus(422);

        $this->postJson("/api/tenant/accounting/journal-entries/{$entryId}/approve", [], $this->tenantHeaders())
            ->assertStatus(422);
    }

    public function test_reversal_entry_creates_opposite_lines(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $entryId = $this->createApprovedEntry();

        $response = $this->postJson("/api/tenant/accounting/journal-entries/{$entryId}/reverse", [
            'reversal_reason' => 'تصحيح خطأ في الترحيل',
        ], $this->tenantHeaders())
            ->assertCreated()
            ->assertJsonPath('data.type', 'reversal')
            ->assertJsonPath('data.status', 'posted');

        $this->getJson("/api/tenant/accounting/journal-entries/{$entryId}", $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'reversed');

        $lines = $response->json('data.lines');
        $this->assertSame(250.0, (float) $lines[0]['credit']);
        $this->assertSame(250.0, (float) $lines[1]['debit']);
    }

    public function test_summary_and_filters_work(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->createApprovedEntry();

        $this->getJson('/api/tenant/accounting/journal-entries/summary', $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.approved_count', 1)
            ->assertJsonPath('data.total_debit', 250)
            ->assertJsonPath('data.total_credit', 250);

        $this->getJson('/api/tenant/accounting/journal-entries?status=posted', $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_gl_summary_and_reports_use_approved_journal_lines(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->createApprovedEntry();

        $cash = Account::query()->where('code', '1000')->firstOrFail();
        $bank = Account::query()->where('code', '1010')->firstOrFail();

        $this->getJson('/api/tenant/accounting/summary?date_from=2026-01-01&date_to=2026-12-31', $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.total_assets', 0)
            ->assertJsonPath('data.journal_entries', 1)
            ->assertJsonPath('data.accounts', Account::query()->where('allow_posting', true)->count());

        $this->getJson('/api/tenant/accounting/accounts-tree?date=2026-05-31', $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.name', 'الأصول')
            ->assertJsonPath('data.0.current_balance', 0);

        $this->getJson('/api/tenant/accounting/reports/balance-sheet?date=2026-05-31', $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.as_of', '2026-05-31')
            ->assertJsonPath('data.assets.total', 0);

        $this->getJson('/api/tenant/accounting/reports/income-statement?date_from=2026-01-01&date_to=2026-12-31', $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.net_income', 0);

        $ledger = $this->getJson(
            "/api/tenant/accounting/ledger?account_id={$cash->id}&date_from=2026-01-01&date_to=2026-12-31",
            $this->tenantHeaders()
        )->assertOk();

        $this->assertSame(250.0, (float) $ledger->json('data.0.debit'));
        $this->assertSame(250.0, (float) $ledger->json('data.0.balance'));

        $bankLedger = $this->getJson(
            "/api/tenant/accounting/ledger?account_id={$bank->id}&date_from=2026-01-01&date_to=2026-12-31",
            $this->tenantHeaders()
        )->assertOk();
        $this->assertSame(250.0, (float) $bankLedger->json('data.0.credit'));
        $this->assertSame(-250.0, (float) $bankLedger->json('data.0.balance'));
    }

    public function test_accounting_core_rejects_invalid_and_immutable_journals(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $cash = Account::query()->where('code', '1000')->firstOrFail();
        $bank = Account::query()->where('code', '1010')->firstOrFail();
        $parent = Account::query()->where('code', '1')->firstOrFail();

        $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 100, 'credit' => 100],
                ['account_id' => $bank->id, 'debit' => 0, 'credit' => 0],
            ],
        ], $this->tenantHeaders())->assertStatus(422);

        $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'lines' => [
                ['account_id' => $parent->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $bank->id, 'credit' => 100, 'debit' => 0],
            ],
        ], $this->tenantHeaders())->assertStatus(422);

        $inactive = Account::query()->where('code', '5200')->firstOrFail();
        $inactive->update(['is_active' => false]);
        $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'lines' => [
                ['account_id' => $inactive->id, 'debit' => 40, 'credit' => 0],
                ['account_id' => $cash->id, 'credit' => 40, 'debit' => 0],
            ],
        ], $this->tenantHeaders())->assertStatus(422);
        $inactive->update(['is_active' => true]);

        $entryId = $this->createApprovedEntry();
        $this->postJson("/api/tenant/accounting/journal-entries/{$entryId}/cancel", [
            'cancellation_reason' => 'should fail',
        ], $this->tenantHeaders())->assertStatus(422);

        $this->deleteJson("/api/tenant/accounting/journal-entries/{$entryId}", [], $this->tenantHeaders())
            ->assertStatus(422);

        $draft = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 10, 'credit' => 0],
                ['account_id' => $bank->id, 'credit' => 10, 'debit' => 0],
            ],
        ], $this->tenantHeaders())->assertCreated();
        $this->deleteJson('/api/tenant/accounting/journal-entries/'.$draft->json('data.id'), [], $this->tenantHeaders())
            ->assertOk();
    }

    public function test_closed_period_blocks_posting(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        \App\Models\Tenant\AccountingPeriod::query()->create([
            'year' => 2026,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        $create = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-15',
            'lines' => [
                ['account_id' => $this->accountIds[0], 'debit' => 80, 'credit' => 0],
                ['account_id' => $this->accountIds[1], 'credit' => 80, 'debit' => 0],
            ],
        ], $this->tenantHeaders())->assertStatus(422);
    }

    public function test_source_tracking_and_report_consistency(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $cash = Account::query()->where('code', '1000')->firstOrFail();
        $revenue = Account::query()->where('code', '4100')->firstOrFail();

        $service = app(\App\Services\Tenant\JournalEntryService::class);
        $entry = $service->createFromSource([
            'entry_date' => '2026-06-01',
            'source_type' => \App\Models\Tenant\JournalEntry::SOURCE_PAYMENT,
            'source_id' => 777,
            'reference_number' => 'PAY-777',
            'description' => 'Source tracked payment',
        ], [
            ['account_id' => $cash->id, 'debit' => 200, 'credit' => 0],
            ['account_id' => $revenue->id, 'credit' => 200, 'debit' => 0],
        ], $this->user->id);

        $this->assertSame('posted', $entry->status);
        $this->assertSame('payment', $entry->source_type);
        $this->assertSame(777, (int) $entry->source_id);

        $bs = $this->getJson('/api/tenant/accounting/reports/balance-sheet?date=2026-06-01', $this->tenantHeaders())->assertOk();
        $is = $this->getJson('/api/tenant/accounting/reports/income-statement?date_from=2026-06-01&date_to=2026-06-01', $this->tenantHeaders())->assertOk();
        $ledger = $this->getJson('/api/tenant/accounting/ledger?account_id='.$cash->id.'&date_from=2026-06-01&date_to=2026-06-01', $this->tenantHeaders())->assertOk();

        $this->assertSame(200.0, (float) $is->json('data.net_income'));
        $this->assertSame(200.0, (float) $bs->json('data.assets.total'));
        $this->assertTrue((bool) $bs->json('data.balanced'));
        $this->assertSame(200.0, (float) $ledger->json('meta.closing_balance'));
    }

    public function test_export_respects_filters(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->createApprovedEntry();

        $response = $this->get('/api/tenant/accounting/journal-entries/export?status=posted', $this->tenantHeaders());
        $response->assertOk();
        $this->assertStringContainsString('journal-entries.csv', (string) $response->headers->get('content-disposition'));
    }

    private function createApprovedEntry(): int
    {
        $create = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-05-31',
            'description' => 'Balanced approved',
            'lines' => [
                ['account_id' => $this->accountIds[0], 'debit' => 250, 'credit' => 0],
                ['account_id' => $this->accountIds[1], 'credit' => 250, 'debit' => 0],
            ],
        ], $this->tenantHeaders())->assertCreated();

        $entryId = (int) $create->json('data.id');
        $this->postJson("/api/tenant/accounting/journal-entries/{$entryId}/approve", [], $this->tenantHeaders())
            ->assertOk();

        return $entryId;
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $this->centralDatabasePath = $testingPath.'/central-journal-entry.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-journal-entry.sqlite';
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

    private function seedTenantPermissions(): void
    {
        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => TenantRolePermissionSeeder::class,
            '--force' => true,
        ]);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Demo Tenant',
            'slug' => 'demo',
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
        $permissionIds = Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all();
        $role->permissions()->sync($permissionIds);
        $user = User::query()->create([
            'name' => 'Tenant User '.uniqid(),
            'email' => uniqid().'@tenant.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    public function test_create_from_source_is_idempotent(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $service = app(\App\Services\Tenant\JournalEntryService::class);
        $header = [
            'entry_date' => '2026-05-31',
            'source_type' => JournalEntry::SOURCE_PAYMENT,
            'source_id' => 999,
            'reference_number' => 'PAY-999',
            'description' => 'Test payment posting',
        ];
        $lines = [
            ['account_id' => $this->accountIds[0], 'debit' => 100, 'credit' => 0],
            ['account_id' => $this->accountIds[1], 'credit' => 100, 'debit' => 0],
        ];

        $first = $service->createFromSource($header, $lines, $this->user->id);
        $second = $service->createFromSource($header, $lines, $this->user->id);

        $this->assertSame($first->id, $second->id);
    }

    /**
     * @return array<string,string>
     */
    private function tenantHeaders(): array
    {
        return ['Accept' => 'application/json', 'X-Tenant' => $this->tenant->slug];
    }

    public function test_tenant_isolation_prevents_cross_tenant_access(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $entryId = $this->createApprovedEntry();

        $otherDatabasePath = storage_path('framework/testing/other-tenant-journal.sqlite');
        $this->prepareSecondaryTenantDatabase($otherDatabasePath, $this->user);

        $otherTenant = Tenant::query()->create([
            'name' => 'Other Tenant',
            'slug' => 'other',
            'database_name' => $otherDatabasePath,
            'status' => 'active',
            'subscription_starts_at' => CarbonImmutable::now()->subDay(),
            'subscription_ends_at' => CarbonImmutable::now()->addDays(10),
        ]);

        $this->getJson("/api/tenant/accounting/journal-entries/{$entryId}", [
            'Accept' => 'application/json',
            'X-Tenant' => $otherTenant->slug,
        ])->assertNotFound();
    }

    private function prepareSecondaryTenantDatabase(string $databasePath, User $mirrorUser): void
    {
        @unlink($databasePath);
        touch($databasePath);

        Config::set('database.connections.tenant.database', $databasePath);
        DB::purge('tenant');
        DB::reconnect('tenant');

        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => base_path('database/migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => TenantRolePermissionSeeder::class,
            '--force' => true,
        ]);

        $ownerRole = Role::query()->where('slug', 'owner')->first();
        $secondaryUser = User::query()->create([
            'name' => $mirrorUser->name,
            'email' => $mirrorUser->email,
            'password' => $mirrorUser->password,
            'status' => 'active',
        ]);
        if ($ownerRole !== null) {
            $secondaryUser->roles()->sync([$ownerRole->id]);
        }

        Config::set('database.connections.tenant.database', $this->tenantDatabasePath);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function test_lifecycle_submit_accept_post_and_approved_cannot_be_deleted(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $cash = Account::query()->where('code', '1000')->firstOrFail();
        $revenue = Account::query()->where('code', '4100')->firstOrFail();

        $create = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => '2026-08-01',
            'description' => 'Lifecycle entry',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0, 'description' => 'استلام نقدي'],
                ['account_id' => $revenue->id, 'credit' => 5000, 'debit' => 0, 'description' => 'مبيعات'],
            ],
        ], $this->tenantHeaders())->assertCreated();

        $id = (int) $create->json('data.id');
        $this->postJson("/api/tenant/accounting/journal-entries/{$id}/submit", [], $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_approval');

        $this->postJson("/api/tenant/accounting/journal-entries/{$id}/accept", [], $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->deleteJson("/api/tenant/accounting/journal-entries/{$id}", [], $this->tenantHeaders())
            ->assertStatus(422);

        $this->getJson("/api/tenant/accounting/ledger?account_id={$cash->id}&date_from=2026-08-01&date_to=2026-08-01", $this->tenantHeaders())
            ->assertOk();
        $this->assertSame([], $this->getJson("/api/tenant/accounting/ledger?account_id={$cash->id}&date_from=2026-08-01&date_to=2026-08-01", $this->tenantHeaders())->json('data'));

        $this->postJson("/api/tenant/accounting/journal-entries/{$id}/post", [], $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'posted');

        $ledger = $this->getJson("/api/tenant/accounting/ledger?account_id={$cash->id}&date_from=2026-08-01&date_to=2026-08-01", $this->tenantHeaders())->assertOk();
        $this->assertSame(5000.0, (float) $ledger->json('data.0.debit'));
        $this->assertNotNull($ledger->json('data.0.journal_id'));
    }

    public function test_opening_balances_post_balanced_journal(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $cash = Account::query()->where('code', '1000')->firstOrFail();
        $bank = Account::query()->where('code', '1010')->firstOrFail();
        $ar = Account::query()->where('code', '1200')->firstOrFail();
        $inventory = Account::query()->where('code', '1300')->firstOrFail();
        $ap = Account::query()->where('code', '2000')->firstOrFail();
        $equity = Account::query()->where('code', '3000')->firstOrFail();

        $this->postJson('/api/tenant/accounting/opening-balances', [
            'entry_date' => '2026-08-01',
            'description' => 'أرصدة افتتاحية',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 20000, 'credit' => 0],
                ['account_id' => $bank->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $ar->id, 'debit' => 15000, 'credit' => 0],
                ['account_id' => $inventory->id, 'debit' => 40000, 'credit' => 0],
                ['account_id' => $ap->id, 'debit' => 0, 'credit' => 25000],
                ['account_id' => $equity->id, 'debit' => 0, 'credit' => 100000],
            ],
        ], $this->tenantHeaders())->assertOk()->assertJsonPath('data.is_balanced', true);

        $batchId = (int) $this->getJson('/api/tenant/accounting/opening-balances', $this->tenantHeaders())->json('data.batch.id');
        $this->postJson("/api/tenant/accounting/opening-balances/{$batchId}/post", [], $this->tenantHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'posted');

        $bs = $this->getJson('/api/tenant/accounting/reports/balance-sheet?date=2026-08-01', $this->tenantHeaders())->assertOk();
        $this->assertTrue((bool) $bs->json('data.balanced'));
        $this->assertSame(125000.0, (float) $bs->json('data.assets.total'));
    }

    public function test_treasury_receive_and_transfer_create_posted_journals(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->user->roles()->first()?->permissions()->syncWithoutDetaching(
            \App\Models\Tenant\Permission::query()->whereIn('key', ['cashboxes.view', 'cashboxes.create', 'cash_movements.create'])->pluck('id')->all()
        );

        $cashAccount = Account::query()->where('code', '1000')->firstOrFail();
        $bankAccount = Account::query()->where('code', '1010')->firstOrFail();
        $ar = Account::query()->where('code', '1200')->firstOrFail();

        $cashbox = \App\Models\Tenant\Cashbox::query()->create([
            'name' => 'صندوق رئيسي',
            'kind' => 'cash',
            'account_id' => $cashAccount->id,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);
        $bank = \App\Models\Tenant\Cashbox::query()->create([
            'name' => 'البنك',
            'kind' => 'bank',
            'account_id' => $bankAccount->id,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $receive = $this->postJson('/api/tenant/cashboxes/receive', [
            'cashbox_id' => $cashbox->id,
            'contra_account_id' => $ar->id,
            'amount' => 1500,
            'movement_date' => '2026-08-05',
            'description' => 'دفعة عميل',
            'reference' => 'PAY-1',
        ], $this->tenantHeaders())->assertCreated();

        $this->assertSame('posted', $receive->json('data.journal.status'));
        $this->assertSame(1500.0, (float) $cashbox->fresh()->current_balance);

        $transfer = $this->postJson('/api/tenant/cashboxes/transfer', [
            'from_cashbox_id' => $cashbox->id,
            'to_cashbox_id' => $bank->id,
            'amount' => 1000,
            'movement_date' => '2026-08-06',
            'description' => 'تحويل للصندوق البنكي',
        ], $this->tenantHeaders())->assertCreated();

        $this->assertSame('posted', $transfer->json('data.journal.status'));
        $this->assertSame(500.0, (float) $cashbox->fresh()->current_balance);
        $this->assertSame(1000.0, (float) $bank->fresh()->current_balance);

        $gl = $this->getJson('/api/tenant/accounting/general-ledger?date_from=2026-08-01&date_to=2026-08-31', $this->tenantHeaders())->assertOk();
        $cashRow = collect($gl->json('data'))->firstWhere('code', '1000');
        $this->assertSame(500.0, (float) $cashRow['closing_balance']);
    }

    public function test_reports_cannot_mutate_balances_and_reverse_requires_reason(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $entryId = $this->createApprovedEntry();

        $this->postJson("/api/tenant/accounting/journal-entries/{$entryId}/reverse", [], $this->tenantHeaders())
            ->assertStatus(422);

        $this->putJson("/api/tenant/accounting/journal-entries/{$entryId}", [
            'description' => 'mutated from report',
        ], $this->tenantHeaders())->assertStatus(422);
    }
}
