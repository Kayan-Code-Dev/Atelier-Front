<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\Tenant\Account;
use App\Models\Tenant\FixedAsset;
use App\Models\Tenant\FixedAssetCategory;
use App\Models\Tenant\FixedAssetDepreciationEntry;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Tenant\AccountSeeder;
use Database\Seeders\Tenant\FixedAssetCategorySeeder;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantFixedAssetTest extends TestCase
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
        $this->seedTenantPermissions();
        $this->tenant = $this->createTenant();
        $this->user = $this->createTenantUserWithPermissions([
            'accounting.view',
            'accounting.assets.view',
            'accounting.assets.create',
            'accounting.assets.edit',
            'accounting.assets.depreciate',
            'accounting.assets.dispose',
            'accounting.assets.transfer',
            'accounting.journal_entries.view',
        ]);
        Artisan::call('db:seed', ['--database' => 'tenant', '--class' => AccountSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--database' => 'tenant', '--class' => FixedAssetCategorySeeder::class, '--force' => true]);
    }

    public function test_create_cash_purchase_posts_balanced_journal_and_opens_asset_from_source(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $category = FixedAssetCategory::query()->where('code', 'FURN')->firstOrFail();
        $cash = Account::query()->where('code', '1000')->firstOrFail();

        $response = $this->postJson('/api/tenant/accounting/assets', [
            'name' => 'مكتب إداري',
            'category_id' => $category->id,
            'purchase_date' => '2026-01-01',
            'placed_in_service_date' => '2026-01-01',
            'acquisition_cost' => 20000,
            'salvage_value' => 2000,
            'useful_life' => 60,
            'useful_life_unit' => 'months',
            'acquisition_method' => 'cash',
            'payment_account_id' => $cash->id,
        ], $this->headers())->assertCreated();

        $assetId = (int) $response->json('data.id');
        $this->assertSame('active', $response->json('data.status'));
        $this->assertEquals(20000, $response->json('data.acquisition_cost'));
        $this->assertEquals(0, $response->json('data.accumulated_depreciation'));
        $this->assertEquals(20000, $response->json('data.net_book_value'));

        $journal = JournalEntry::query()->where('source_type', JournalEntry::SOURCE_FIXED_ASSET)->where('source_id', $assetId)->first();
        $this->assertNotNull($journal);
        $this->assertTrue((bool) $journal->is_balanced);
        $this->assertEquals($journal->total_debit, $journal->total_credit);
        $this->assertSame('/accounting/assets/'.$assetId, $response->json('data.purchase_journal') ? '/accounting/assets/'.$assetId : null);

        $this->getJson('/api/tenant/accounting/journal-entries/'.$journal->id, $this->headers())
            ->assertOk()
            ->assertJsonPath('data.source_type', 'fixed_asset')
            ->assertJsonPath('data.source_id', $assetId)
            ->assertJsonPath('data.source_url', '/accounting/assets/'.$assetId);
    }

    public function test_payable_purchase_credits_supplier_and_rejects_invalid_values(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $category = FixedAssetCategory::query()->where('code', 'EQPT')->firstOrFail();
        $supplier = Account::query()->where('code', '2000')->firstOrFail();

        $this->postJson('/api/tenant/accounting/assets', [
            'name' => 'آلة',
            'category_id' => $category->id,
            'purchase_date' => '2026-02-01',
            'acquisition_cost' => 1000,
            'salvage_value' => 2000,
            'useful_life' => 12,
            'acquisition_method' => 'payable',
            'payment_account_id' => $supplier->id,
        ], $this->headers())->assertStatus(422);

        $create = $this->postJson('/api/tenant/accounting/assets', [
            'name' => 'آلة خياطة',
            'category_id' => $category->id,
            'purchase_date' => '2026-02-01',
            'acquisition_cost' => 9000,
            'salvage_value' => 0,
            'useful_life' => 36,
            'acquisition_method' => 'payable',
            'payment_account_id' => $supplier->id,
        ], $this->headers())->assertCreated();

        $journal = JournalEntry::query()->findOrFail((int) $create->json('data.purchase_journal_entry_id'));
        $creditLine = $journal->lines()->where('account_id', $supplier->id)->first();
        $this->assertNotNull($creditLine);
        $this->assertEquals(9000, (float) $creditLine->credit);
    }

    public function test_depreciation_posts_once_then_blocks_duplicate_and_stops_when_fully_depreciated(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $assetId = $this->createAsset(1200, 0, 3, '2026-01-01');

        $preview = $this->getJson('/api/tenant/accounting/assets/depreciation?period=2026-01', $this->headers())
            ->assertOk();
        $this->assertSame(1, $preview->json('data.assets_count'));
        $this->assertEquals(400, $preview->json('data.total_depreciation'));

        $this->postJson('/api/tenant/accounting/assets/depreciation', ['period' => '2026-01'], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.already_posted', true);

        $this->postJson('/api/tenant/accounting/assets/depreciation', ['period' => '2026-01'], $this->headers())
            ->assertStatus(422);

        $this->assertSame(1, FixedAssetDepreciationEntry::query()->where('period', '2026-01')->where('status', 'posted')->count());

        $this->postJson('/api/tenant/accounting/assets/depreciation', ['period' => '2026-02'], $this->headers())->assertOk();
        $this->postJson('/api/tenant/accounting/assets/depreciation', ['period' => '2026-03'], $this->headers())->assertOk();

        $asset = $this->getJson('/api/tenant/accounting/assets/'.$assetId, $this->headers())->assertOk();
        $this->assertSame('fully_depreciated', $asset->json('data.status'));
        $this->assertEquals(1200, $asset->json('data.accumulated_depreciation'));
        $this->assertEquals(0, $asset->json('data.net_book_value'));

        $this->postJson('/api/tenant/accounting/assets/depreciation', ['period' => '2026-04'], $this->headers())
            ->assertStatus(422);
    }

    public function test_dispose_with_gain_and_loss_and_transfer_without_journal(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $gainId = $this->createAsset(20000, 2000, 60, '2026-01-01');
        $this->postJson('/api/tenant/accounting/assets/depreciation', ['period' => '2026-01'], $this->headers())->assertOk();

        $preview = $this->getJson('/api/tenant/accounting/assets/'.$gainId.'/disposal-preview?proceeds=20000', $this->headers())->json('data');
        $this->assertEquals(20000, $preview['acquisition_cost']);
        $this->assertTrue($preview['gain_loss'] > 0);

        $this->postJson('/api/tenant/accounting/assets/'.$gainId.'/dispose', [
            'type' => 'sale',
            'disposed_at' => '2026-02-01',
            'proceeds' => 20000,
            'proceeds_account_id' => Account::query()->where('code', '1000')->value('id'),
        ], $this->headers())->assertOk()->assertJsonPath('data.status', 'disposed');

        $lossId = $this->createAsset(5000, 0, 10, '2026-01-01', 'LOSS-1');
        $this->postJson('/api/tenant/accounting/assets/'.$lossId.'/dispose', [
            'type' => 'loss',
            'disposed_at' => '2026-02-01',
            'proceeds' => 0,
        ], $this->headers())->assertOk()->assertJsonPath('data.status', 'retired');

        $transferId = $this->createAsset(8000, 0, 24, '2026-01-01', 'TR-1');
        $before = JournalEntry::query()->count();
        $this->postJson('/api/tenant/accounting/assets/'.$transferId.'/transfer', [
            'transferred_at' => '2026-03-01',
            'to_location' => 'المخزن الرئيسي',
            'reason' => 'إعادة تنظيم',
        ], $this->headers())->assertOk()->assertJsonPath('data.location', 'المخزن الرئيسي');
        $this->assertSame($before, JournalEntry::query()->count());
    }

    public function test_branch_isolation_hides_other_branch_assets_from_depreciation_preview(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->createAsset(3000, 0, 12, '2026-01-01', 'BR-A');
        $preview = $this->getJson('/api/tenant/accounting/assets/depreciation?period=2026-01&branch_id=999', $this->headers())
            ->assertOk();
        $this->assertSame(0, $preview->json('data.assets_count'));
    }

    private function createAsset(float $cost, float $salvage, int $life, string $date, ?string $code = null): int
    {
        $category = FixedAssetCategory::query()->where('code', 'COMP')->firstOrFail();
        $response = $this->postJson('/api/tenant/accounting/assets', [
            'name' => 'أصل '.$cost,
            'asset_code' => $code,
            'category_id' => $category->id,
            'purchase_date' => $date,
            'placed_in_service_date' => $date,
            'acquisition_cost' => $cost,
            'salvage_value' => $salvage,
            'useful_life' => $life,
            'useful_life_unit' => 'months',
            'acquisition_method' => 'cash',
            'payment_account_id' => Account::query()->where('code', '1000')->value('id'),
        ], $this->headers())->assertCreated();

        return (int) $response->json('data.id');
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }
        $this->centralDatabasePath = $testingPath.'/central-fixed-assets.sqlite';
        $this->tenantDatabasePath = $testingPath.'/tenant-fixed-assets.sqlite';
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
        Artisan::call('db:seed', ['--database' => 'tenant', '--class' => TenantRolePermissionSeeder::class, '--force' => true]);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Assets Tenant',
            'slug' => 'assets',
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
            'name' => 'Asset User',
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
