<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Models\Central\TenantProvisioningLog;
use App\Models\Tenant\User;
use Database\Seeders\Tenant\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class TenantDataResetService
{
    public const CONFIRMATION_WORD = 'تصفير';

    /**
     * @var list<string>
     */
    private const PRESERVED_TABLES = [
        'migrations',
        'users',
        'roles',
        'permissions',
        'role_user',
        'permission_role',
        'settings',
        'personal_access_tokens',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sqlite_sequence',
        'sqlite_stat1',
    ];

    /**
     * @var array<string, array{label: string, tables: list<string>}>
     */
    private const CATEGORIES = [
        'invoices' => [
            'label' => 'الفواتير وبنودها ومدفوعاتها وتسليمها ومرتجعاتها',
            'tables' => [
                'invoices',
                'invoice_items',
                'invoice_payments',
                'delivery_records',
                'security_deposit_transactions',
                'rental_return_settlements',
                'tailoring_stage_histories',
            ],
        ],
        'customers' => [
            'label' => 'العملاء',
            'tables' => ['customers'],
        ],
        'inventory' => [
            'label' => 'الفساتين والتصنيفات والمخزون وصور المنتجات',
            'tables' => ['dresses', 'dress_images', 'dress_categories', 'inventory_movements'],
        ],
        'purchasing' => [
            'label' => 'الموردين وأوامر الشراء ومدفوعات الموردين',
            'tables' => ['suppliers', 'purchase_orders', 'purchase_order_items', 'supplier_payments'],
        ],
        'treasury' => [
            'label' => 'الصناديق والحركات النقدية',
            'tables' => ['cashboxes', 'cash_movements'],
        ],
        'accounting' => [
            'label' => 'القيود المحاسبية والأرصدة الافتتاحية وسجلات المحاسبة',
            'tables' => [
                'journal_entry_lines',
                'journal_entries',
                'opening_balance_lines',
                'opening_balance_batches',
                'accounting_events',
                'accounting_audit_logs',
                'accounting_periods',
                'accounts',
            ],
        ],
        'expenses' => [
            'label' => 'المصروفات وتصنيفاتها',
            'tables' => ['expenses', 'expense_categories'],
        ],
        'hr' => [
            'label' => 'الموارد البشرية والرواتب والحضور والإجازات',
            'tables' => [
                'hr_payroll_payments',
                'hr_payroll_adjustments',
                'hr_attendance_records',
                'hr_leave_requests',
                'hr_documents',
                'hr_employees',
                'hr_shifts',
                'hr_job_titles',
                'hr_departments',
                'hr_settings',
                'employees',
                'employee_custodies',
                'employee_salaries',
                'employee_activity_logs',
            ],
        ],
        'operations' => [
            'label' => 'الورش والمصانع والتحويلات',
            'tables' => ['workshop_transfers', 'workshop_cloths', 'workshops', 'factories'],
        ],
        'website' => [
            'label' => 'الموقع الإلكتروني والطلبات والحجوزات والرسائل',
            'tables' => [
                'website_booking_requests',
                'website_messages',
                'website_leads',
                'website_forms',
                'website_product_publications',
                'website_services',
                'website_gallery_images',
                'website_gallery_albums',
                'website_media',
                'website_sections',
                'website_menus',
                'website_pages',
                'website_sites',
            ],
        ],
        'branches' => [
            'label' => 'الفروع',
            'tables' => ['branches'],
        ],
        'notifications' => [
            'label' => 'الإشعارات وسجل الإعداد الأولي',
            'tables' => ['notifications', 'trial_onboarding_states'],
        ],
        'fixed_assets' => [
            'label' => 'الأصول الثابتة والإهلاك والتصرفات',
            'tables' => [
                'fixed_asset_transactions',
                'fixed_asset_disposals',
                'fixed_asset_transfers',
                'fixed_asset_depreciation_entries',
                'fixed_asset_depreciation_runs',
                'fixed_asset_depreciation_schedules',
                'fixed_assets',
                'fixed_asset_categories',
            ],
        ],
        'equity_liabilities' => [
            'label' => 'حقوق الملكية والالتزامات والقروض',
            'tables' => ['loan_settlements', 'liabilities', 'equity_operations'],
        ],
    ];

    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @return array{
     *     confirmation_word: string,
     *     kept: list<string>,
     *     categories: list<array{key: string, label: string, count: int}>,
     *     total_records: int
     * }
     */
    public function preview(User $actor): array
    {
        $this->assertOwner($actor);

        $existing = $this->existingTables();
        $categories = [];
        $total = 0;
        $classified = [];

        foreach (self::CATEGORIES as $key => $definition) {
            $count = 0;
            foreach ($definition['tables'] as $table) {
                if (! in_array($table, $existing, true)) {
                    continue;
                }
                $classified[$table] = true;
                $count += $this->tableCount($table);
            }

            $categories[] = [
                'key' => $key,
                'label' => $definition['label'],
                'count' => $count,
            ];
            $total += $count;
        }

        $otherCount = 0;
        foreach ($this->resettableTables($existing) as $table) {
            if (isset($classified[$table])) {
                continue;
            }
            $otherCount += $this->tableCount($table);
        }

        if ($otherCount > 0) {
            $categories[] = [
                'key' => 'other',
                'label' => 'بيانات تشغيلية أخرى',
                'count' => $otherCount,
            ];
            $total += $otherCount;
        }

        return [
            'confirmation_word' => self::CONFIRMATION_WORD,
            'kept' => [
                'المستخدمون وتسجيل الدخول',
                'الأدوار والصلاحيات',
                'إعدادات التطبيق الحالية',
                'الاشتراك والخطة واسم الحساب',
            ],
            'categories' => $categories,
            'total_records' => $total,
        ];
    }

    /**
     * @return array{
     *     confirmation_word: string,
     *     kept: list<string>,
     *     categories: list<array{key: string, label: string, count: int}>,
     *     total_records: int
     * }
     */
    public function reset(User $actor): array
    {
        $this->assertOwner($actor);

        $tenant = $this->tenantContext->requireTenant();
        $preview = $this->preview($actor);
        $tables = $this->resettableTables($this->existingTables());

        DB::connection('tenant')->transaction(function () use ($tables): void {
            $schema = Schema::connection('tenant');
            $schema->disableForeignKeyConstraints();

            try {
                if ($schema->hasColumn('users', 'branch_id')) {
                    DB::connection('tenant')->table('users')->update(['branch_id' => null]);
                }

                foreach ($tables as $table) {
                    $this->emptyTable($table);
                }
            } finally {
                $schema->enableForeignKeyConstraints();
            }

            (new TenantRolePermissionSeeder())->run();
        });

        $this->resetAutoIncrement($tables);
        $this->logReset($tenant, $actor, $preview['total_records']);

        return $preview;
    }

    private function assertOwner(User $actor): void
    {
        if (! $actor->isOwner()) {
            throw new RuntimeException('OWNER_REQUIRED');
        }
    }

    /**
     * @param  list<string>  $existing
     * @return list<string>
     */
    private function resettableTables(array $existing): array
    {
        $preserved = array_fill_keys(self::PRESERVED_TABLES, true);
        $tables = [];

        foreach ($existing as $table) {
            if (isset($preserved[$table]) || str_starts_with($table, 'sqlite_')) {
                continue;
            }
            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * @return list<string>
     */
    private function existingTables(): array
    {
        $schema = Schema::connection('tenant');
        $tables = $schema->getTableListing(null, false);

        return array_values(array_unique(array_map(
            fn ($table): string => $this->unqualifiedTableName((string) $table),
            $tables,
        )));
    }

    private function unqualifiedTableName(string $table): string
    {
        $table = trim($table, '`"');
        if (str_contains($table, '.')) {
            $table = substr($table, strrpos($table, '.') + 1);
        }

        return $table;
    }

    private function tableCount(string $table): int
    {
        try {
            return (int) DB::connection('tenant')->table($table)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function emptyTable(string $table): void
    {
        $table = $this->unqualifiedTableName($table);
        if (in_array($table, self::PRESERVED_TABLES, true) || str_starts_with($table, 'sqlite_')) {
            return;
        }

        $connection = DB::connection('tenant');
        $connection->table($table)->delete();

        if ($connection->getDriverName() === 'sqlite' && Schema::connection('tenant')->hasTable('sqlite_sequence')) {
            $connection->table('sqlite_sequence')->where('name', $table)->delete();
        }
    }

    /**
     * @param  list<string>  $tables
     */
    private function resetAutoIncrement(array $tables): void
    {
        $connection = DB::connection('tenant');
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ($tables as $table) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                continue;
            }

            try {
                $connection->statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            } catch (Throwable) {
                // IDs may continue from the previous sequence; the tables are already empty.
            }
        }
    }

    private function logReset(Tenant $tenant, User $actor, int $totalRecords): void
    {
        try {
            TenantProvisioningLog::query()->create([
                'tenant_id' => $tenant->id,
                'step' => 'tenant_data_reset',
                'status' => 'success',
                'message' => 'تم تصفير بيانات التينانت وإعادتها لحساب جديد',
                'context' => [
                    'actor_id' => $actor->id,
                    'actor_email' => $actor->email,
                    'total_records' => $totalRecords,
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
