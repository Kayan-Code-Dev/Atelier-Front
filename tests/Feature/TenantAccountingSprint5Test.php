<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\Tenant\Account;
use App\Models\Tenant\AccountingAuditLog;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\Permission;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Role;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\AccountSeeder;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantAccountingSprint5Test extends TestCase
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
            'accounting.controls.view',
            'accounting.journal_entries.view',
            'accounting.journal_entries.create',
            'accounting.journal_entries.update',
            'accounting.journal_entries.approve',
            'accounting.reconciliation.view',
            'accounting.reconciliation.create',
            'accounting.reconciliation.match',
            'accounting.reconciliation.adjust',
            'accounting.reconciliation.lock',
            'accounting.receivables.view',
            'accounting.receivables.export',
            'accounting.payables.view',
            'accounting.payables.export',
        ]);
        Artisan::call('db:seed', ['--database' => 'tenant', '--class' => AccountSeeder::class, '--force' => true]);
    }

    public function test_create_bank_account_masks_number_and_never_returns_full_iban(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $response = $this->postJson('/api/tenant/accounting/treasury/banks', [
            'name' => 'الحساب الجاري',
            'bank_name' => 'البنك الأهلي',
            'account_number' => '1234564821',
            'iban' => 'LY12345678901234567890123',
            'currency' => 'LYD',
            'account_id' => $this->id('1010'),
            'opening_balance' => 0,
        ], $this->headers())->assertCreated();

        $this->assertSame('•••• 4821', $response->json('data.account_number_masked'));
        $this->assertSame('•••• 0123', $response->json('data.iban_masked'));
        $this->assertStringNotContainsString('1234564821', $response->getContent());
        $this->assertStringNotContainsString('LY12345678901234567890123', $response->getContent());
        $this->assertDatabaseHas('bank_accounts', [
            'bank_name' => 'البنك الأهلي',
            'account_number_last4' => '4821',
        ], 'tenant');
        $this->assertNull(BankAccount::query()->first()?->getAttribute('account_number'));
    }

    public function test_import_statement_validates_rows_and_prevents_duplicates(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $recon = $this->startReconciliation(0);

        $csv = $this->csv([
            ['05/08/2026', 'TRANSFER FROM AHMED', 'OP-1', '0', '5000', '5000'],
        ]);
        $file = UploadedFile::fake()->createWithContent('stmt.csv', $csv);

        $this->post('/api/tenant/accounting/reconciliations/'.$recon.'/statements/preview', [
            'file' => $file,
        ], $this->headers())->assertOk()->assertJsonPath('data.row_count', 1);

        $this->post('/api/tenant/accounting/reconciliations/'.$recon.'/statements', [
            'file' => UploadedFile::fake()->createWithContent('stmt.csv', $csv),
        ], $this->headers())->assertOk()->assertJsonPath('data.row_count', 1);

        $this->post('/api/tenant/accounting/reconciliations/'.$recon.'/statements', [
            'file' => UploadedFile::fake()->createWithContent('stmt.csv', $csv),
        ], $this->headers())->assertStatus(422);

        $this->assertTrue(AccountingAuditLog::query()->where('action', 'bank_statement_imported')->exists());
    }

    public function test_exact_matching_auto_matches_and_does_not_mutate_journal(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $journalId = $this->postJournal('2026-08-05', '1010', 5000, '1200', 5000, 'Customer Payment');
        $before = JournalEntry::query()->findOrFail($journalId);
        $recon = $this->startReconciliation(5000);

        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/statements', [
            'rows' => [[
                'date' => '2026-08-05',
                'description' => 'TRANSFER FROM AHMED',
                'reference' => 'Customer Payment',
                'debit' => 0,
                'credit' => 5000,
                'amount' => 5000,
            ]],
        ], $this->headers())->assertOk();

        $matched = $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/auto-match', [], $this->headers())
            ->assertOk();
        $this->assertNotEmpty($matched->json('data.matches'));
        $this->assertSame('exact', $matched->json('data.matches.0.grade'));

        $after = JournalEntry::query()->findOrFail($journalId);
        $this->assertSame((string) $before->total_debit, (string) $after->total_debit);
        $this->assertSame((string) $before->total_credit, (string) $after->total_credit);
        $this->assertSame($before->description, $after->description);
        $this->assertSame($before->source_type, $after->source_type);
    }

    public function test_manual_matching_creates_independent_record(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $journalId = $this->postJournal('2026-08-05', '1010', 5000, '1200', 5000, 'Customer Payment');
        $lineId = (int) DB::connection('tenant')->table('journal_entry_lines')
            ->where('journal_entry_id', $journalId)
            ->where('account_id', $this->id('1010'))
            ->value('id');
        $recon = $this->startReconciliation(5000);
        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/statements', [
            'rows' => [[
                'date' => '2026-08-08',
                'description' => 'TRANSFER FROM AHMED',
                'reference' => 'X-99',
                'credit' => 5000,
                'amount' => 5000,
            ]],
        ], $this->headers())->assertOk();

        $auto = $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/auto-match', [], $this->headers())->assertOk();
        $this->assertEmpty($auto->json('data.matches'));

        $statementLineId = (int) $auto->json('data.reconciliation.statement_lines.0.id');
        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/match', [
            'statement_line_id' => $statementLineId,
            'journal_entry_line_id' => $lineId,
        ], $this->headers())->assertOk()->assertJsonPath('data.match.match_type', 'manual');

        $this->assertTrue(AccountingAuditLog::query()->where('action', 'reconciliation_manual_matched')->exists());
        $this->assertSame('posted', JournalEntry::query()->findOrFail($journalId)->status);
    }

    public function test_unmatched_transactions_and_bank_adjustment_use_journal_engine(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-05', '1010', 5000, '1200', 5000, 'Customer Payment');
        $recon = $this->startReconciliation(4950);
        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/statements', [
            'rows' => [
                ['date' => '2026-08-05', 'description' => 'TRANSFER FROM AHMED', 'credit' => 5000, 'amount' => 5000],
                ['date' => '2026-08-06', 'description' => 'BANK FEE', 'debit' => 50, 'amount' => -50],
            ],
        ], $this->headers())->assertOk();

        $detail = $this->getJson('/api/tenant/accounting/reconciliations/'.$recon, $this->headers())->assertOk();
        $this->assertNotEmpty($detail->json('data.outstanding.in_bank_not_in_books.fees'));

        $adjust = $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/adjustments', [
            'kind' => 'bank_fee',
            'amount' => 50,
            'description' => 'رسوم بنكية 50',
            'entry_date' => '2026-08-06',
        ], $this->headers())->assertOk();

        $entryId = (int) $adjust->json('data.journal_entry_id');
        $entry = JournalEntry::query()->findOrFail($entryId);
        $this->assertSame(JournalEntry::SOURCE_BANK_RECONCILIATION, $entry->source_type);
        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->status);
        $this->assertEquals(50, (float) $entry->total_debit);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $entryId,
            'account_id' => $this->id('5500'),
        ], 'tenant');
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $entryId,
            'account_id' => $this->id('1010'),
        ], 'tenant');
    }

    public function test_reconciliation_calculation_lock_and_unreconciled_difference(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-05', '1010', 5000, '1200', 5000, 'Customer Payment');
        $recon = $this->startReconciliation(120500);
        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/reconcile', [], $this->headers())
            ->assertStatus(422);

        $balanced = $this->startReconciliation(5000);
        $this->postJson('/api/tenant/accounting/reconciliations/'.$balanced.'/statements', [
            'rows' => [[
                'date' => '2026-08-05',
                'description' => 'TRANSFER FROM AHMED',
                'reference' => 'Customer Payment',
                'credit' => 5000,
                'amount' => 5000,
            ]],
        ], $this->headers())->assertOk();
        $this->postJson('/api/tenant/accounting/reconciliations/'.$balanced.'/auto-match', [], $this->headers())->assertOk();
        $summary = $this->getJson('/api/tenant/accounting/reconciliations/'.$balanced, $this->headers())->assertOk();
        $this->assertEquals(0, $summary->json('data.summary.difference'));
        $this->postJson('/api/tenant/accounting/reconciliations/'.$balanced.'/reconcile', [], $this->headers())->assertOk();
        $this->postJson('/api/tenant/accounting/reconciliations/'.$balanced.'/lock', [], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.status', 'locked');

        $this->postJson('/api/tenant/accounting/reconciliations/'.$balanced.'/match', [
            'statement_line_id' => 1,
            'journal_entry_line_id' => 1,
        ], $this->headers())->assertStatus(422);

        $this->assertTrue(AccountingAuditLog::query()->where('action', 'reconciliation_locked')->exists());
    }

    public function test_lock_requires_dedicated_permission(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-05', '1010', 5000, '1200', 5000, 'Customer Payment');
        $recon = $this->startReconciliation(5000);
        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/statements', [
            'rows' => [[
                'date' => '2026-08-05',
                'description' => 'TRANSFER FROM AHMED',
                'reference' => 'Customer Payment',
                'credit' => 5000,
                'amount' => 5000,
            ]],
        ], $this->headers())->assertOk();
        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/auto-match', [], $this->headers())->assertOk();
        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/reconcile', [], $this->headers())->assertOk();

        $limited = $this->createTenantUserWithPermissions([
            'accounting.view',
            'accounting.reconciliation.view',
            'accounting.reconciliation.create',
        ]);
        Sanctum::actingAs($limited, ['*']);
        $this->postJson('/api/tenant/accounting/reconciliations/'.$recon.'/lock', [], $this->headers())->assertStatus(403);
    }

    public function test_receivables_invoice_payment_aging_and_customer_statement(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $customer = Customer::query()->create(['name' => 'أحمد', 'phone' => '0910000001']);
        Invoice::query()->create([
            'invoice_number' => 'INV-AR-1',
            'customer_id' => $customer->id,
            'type' => Invoice::TYPE_SELL,
            'status' => Invoice::STATUS_PARTIALLY_PAID,
            'subtotal' => 20000,
            'total' => 20000,
            'paid_amount' => 15000,
            'remaining_amount' => 5000,
            'delivery_date' => now()->subDays(45)->toDateString(),
        ]);

        $list = $this->getJson('/api/tenant/accounting/receivables', $this->headers())->assertOk();
        $this->assertEquals(5000, $list->json('data.customers.0.outstanding'));
        $this->assertEquals(5000, $list->json('data.customers.0.aging.31_60'));
        $this->assertFalse($list->json('data.reconciles_to_gl'));

        $this->postJournal('2026-08-01', '1200', 5000, '4100', 5000, 'Invoice AR');
        $matched = $this->getJson('/api/tenant/accounting/receivables', $this->headers())->assertOk();
        $this->assertTrue($matched->json('data.reconciles_to_gl'));

        $statement = $this->getJson('/api/tenant/accounting/receivables/'.$customer->id, $this->headers())->assertOk();
        $this->assertSame('أحمد', $statement->json('data.customer.name'));
        $this->assertNotEmpty($statement->json('data.lines'));
        $this->assertSame('invoice', $statement->json('data.lines.0.source_type'));
    }

    public function test_payables_supplier_balance_aging_and_statement(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $supplier = Supplier::query()->create([
            'name' => 'مورد الأقمشة',
            'status' => 'active',
            'opening_balance' => 0,
            'current_balance' => 3000,
        ]);
        PurchaseOrder::query()->create([
            'supplier_id' => $supplier->id,
            'purchase_order_number' => 'PO-AP-1',
            'status' => PurchaseOrder::STATUS_PARTIALLY_PAID,
            'total' => 8000,
            'paid_amount' => 5000,
            'remaining_amount' => 3000,
            'order_date' => now()->subDays(10)->toDateString(),
        ]);

        $list = $this->getJson('/api/tenant/accounting/payables', $this->headers())->assertOk();
        $this->assertEquals(3000, $list->json('data.suppliers.0.outstanding'));
        $this->assertEquals(3000, $list->json('data.suppliers.0.aging.1_30'));

        $this->postJournal('2026-08-01', '1300', 3000, '2000', 3000, 'Supplier bill');
        $this->assertTrue($this->getJson('/api/tenant/accounting/payables', $this->headers())->json('data.reconciles_to_gl'));

        $statement = $this->getJson('/api/tenant/accounting/payables/'.$supplier->id, $this->headers())->assertOk();
        $this->assertSame('مورد الأقمشة', $statement->json('data.supplier.name'));
        $this->assertSame('purchase_order', $statement->json('data.lines.0.source_type'));
    }

    public function test_negative_cash_is_visible_and_not_auto_fixed(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-10', '5000', 80, '1000', 80, 'Overpay cash');

        $summary = $this->getJson('/api/tenant/accounting/summary?period=month', $this->headers())->assertOk();
        $this->assertTrue($summary->json('data.negative_cash'));
        $this->assertLessThan(0, $summary->json('data.cash_balance'));

        $exceptions = $this->getJson('/api/tenant/accounting/exceptions', $this->headers())->assertOk();
        $codes = collect($exceptions->json('data'))->pluck('code')->all();
        $this->assertContains('negative_cash', $codes);
        $this->assertEquals(-80, $this->getJson('/api/tenant/accounting/summary?period=month', $this->headers())->json('data.cash_balance'));
    }

    public function test_controls_include_treasury_reconciliation_and_sprint5_accounts_seeded(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJson('/api/tenant/accounting/treasury/banks', [
            'name' => 'جاري',
            'bank_name' => 'Bank B',
            'account_number' => '99994821',
            'account_id' => $this->id('1010'),
        ], $this->headers())->assertCreated();

        $controls = $this->getJson('/api/tenant/accounting/controls', $this->headers())->assertOk();
        $this->assertNotEmpty($controls->json('data.treasury_reconciliation.items'));
        foreach (AccountSeeder::PHASE5_ACCOUNT_CODES as $code) {
            $this->assertTrue(Account::query()->where('code', $code)->exists(), $code);
        }
    }

    public function test_sprint4_income_statement_regression(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->postJournal('2026-08-10', '1000', 8000, '4100', 8000);
        $this->postJournal('2026-08-12', '5000', 1500, '1000', 1500);
        $response = $this->getJson(
            '/api/tenant/accounting/reports/income-statement?date_from=2026-08-01&date_to=2026-08-31',
            $this->headers()
        )->assertOk();
        $this->assertEquals(8000, $response->json('data.gross_revenue'));
        $this->assertEquals(6500, $response->json('data.net_profit'));
    }

    private function startReconciliation(float $statementBalance): int
    {
        $existingId = (int) BankAccount::query()->value('id');
        if ($existingId > 0) {
            $bankId = $existingId;
        } else {
            $bank = $this->postJson('/api/tenant/accounting/treasury/banks', [
                'name' => 'الحساب الجاري',
                'bank_name' => 'البنك الأهلي',
                'account_number' => '55554821',
                'account_id' => $this->id('1010'),
            ], $this->headers())->assertCreated();
            $bankId = (int) $bank->json('data.id');
        }

        $create = $this->postJson('/api/tenant/accounting/reconciliations', [
            'bank_account_id' => $bankId,
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
            'statement_balance' => $statementBalance,
        ], $this->headers())->assertCreated();

        return (int) $create->json('data.id');
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function csv(array $rows): string
    {
        $lines = ['Date,Description,Reference,Debit,Credit,Amount'];
        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        return implode("\n", $lines);
    }

    private function postJournal(string $date, string $debitCode, float $amount, string $creditCode, float $creditAmount, string $description = 'Sprint5'): int
    {
        $create = $this->postJson('/api/tenant/accounting/journal-entries', [
            'entry_date' => $date,
            'description' => $description,
            'reference_number' => $description,
            'lines' => [
                ['account_id' => $this->id($debitCode), 'debit' => $amount, 'credit' => 0, 'description' => $description],
                ['account_id' => $this->id($creditCode), 'credit' => $creditAmount, 'debit' => 0, 'description' => $description],
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
        $this->centralDatabasePath = $testingPath.'/central-sprint5.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-sprint5.sqlite';
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
            'name' => 'Sprint5 Tenant',
            'slug' => 'sprint5',
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
            'name' => 'Sprint5 User',
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
