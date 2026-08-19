<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /** @var list<string> Phase 2 commercial accounting codes (deposit liability + fee revenue). */
    public const PHASE2_ACCOUNT_CODES = ['2100', '4200', '4210', '4220'];

    /** @var list<string> Phase 3 fixed-asset / equity / liability codes. */
    public const PHASE3_ACCOUNT_CODES = ['1400', '1410', '1420', '1430', '1440', '1450', '1460', '1490', '2200', '2290', '3100', '4300', '5300'];

    /** @var list<string> Phase 4 statement grouping codes (returns, utilities, marketing, COGS). */
    public const PHASE4_ACCOUNT_CODES = ['4900', '5110', '5120', '5400'];

    /** @var list<string> Phase 5 bank fees / interest codes. */
    public const PHASE5_ACCOUNT_CODES = ['4310', '5500'];

    public function run(): void
    {
        $parents = [
            ['code' => '1', 'name' => 'الأصول', 'type' => 'asset'],
            ['code' => '2', 'name' => 'الخصوم', 'type' => 'liability'],
            ['code' => '3', 'name' => 'حقوق الملكية', 'type' => 'equity'],
            ['code' => '4', 'name' => 'الإيرادات', 'type' => 'revenue'],
            ['code' => '5', 'name' => 'المصروفات', 'type' => 'expense'],
        ];

        $parentIds = [];
        foreach ($parents as $parent) {
            $row = Account::query()->updateOrCreate(
                ['code' => $parent['code']],
                [
                    'name' => $parent['name'],
                    'type' => $parent['type'],
                    'normal_balance' => in_array($parent['type'], ['asset', 'expense'], true) ? 'debit' : 'credit',
                    'is_active' => true,
                    'is_system' => true,
                    'allow_posting' => false,
                    'parent_id' => null,
                ]
            );
            $parentIds[$parent['type']] = $row->id;
        }

        $accounts = [
            ['code' => '1000', 'name' => 'الصندوق', 'type' => 'asset'],
            ['code' => '1010', 'name' => 'البنك', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'العملاء', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'المخزون', 'type' => 'asset'],
            ['code' => '1400', 'name' => 'الأصول الثابتة', 'type' => 'asset', 'allow_posting' => false],
            ['code' => '1410', 'name' => 'أثاث', 'type' => 'asset'],
            ['code' => '1420', 'name' => 'معدات', 'type' => 'asset'],
            ['code' => '1430', 'name' => 'أجهزة كمبيوتر', 'type' => 'asset'],
            ['code' => '1440', 'name' => 'أجهزة كهربائية', 'type' => 'asset'],
            ['code' => '1450', 'name' => 'سيارات', 'type' => 'asset'],
            ['code' => '1460', 'name' => 'تجهيزات', 'type' => 'asset'],
            ['code' => '1490', 'name' => 'مجمع إهلاك الأصول الثابتة', 'type' => 'asset', 'normal_balance' => 'credit'],
            ['code' => '2000', 'name' => 'الموردون', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'ودائع تأمين قابلة للاسترداد', 'type' => 'liability'],
            ['code' => '2200', 'name' => 'قروض', 'type' => 'liability'],
            ['code' => '2290', 'name' => 'التزامات أخرى', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'رأس المال', 'type' => 'equity'],
            ['code' => '3100', 'name' => 'مسحوبات المالك', 'type' => 'equity', 'normal_balance' => 'debit'],
            ['code' => '4000', 'name' => 'إيرادات الإيجار', 'type' => 'revenue'],
            ['code' => '4100', 'name' => 'إيرادات البيع', 'type' => 'revenue'],
            ['code' => '4200', 'name' => 'إيرادات غرامة التأخير', 'type' => 'revenue'],
            ['code' => '4210', 'name' => 'إيرادات أضرار', 'type' => 'revenue'],
            ['code' => '4220', 'name' => 'إيرادات تنظيف', 'type' => 'revenue'],
            ['code' => '4300', 'name' => 'أرباح وخسائر التصرف في الأصول', 'type' => 'revenue'],
            ['code' => '4310', 'name' => 'إيرادات فوائد بنكية', 'type' => 'revenue'],
            ['code' => '4900', 'name' => 'مردودات وخصومات المبيعات', 'type' => 'revenue', 'normal_balance' => 'debit'],
            ['code' => '5000', 'name' => 'مصروفات تشغيل', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'مصروفات إيجار', 'type' => 'expense'],
            ['code' => '5110', 'name' => 'مصروفات خدمات', 'type' => 'expense'],
            ['code' => '5120', 'name' => 'مصروفات تسويق', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'مصروفات رواتب', 'type' => 'expense'],
            ['code' => '5300', 'name' => 'مصروف إهلاك', 'type' => 'expense'],
            ['code' => '5400', 'name' => 'تكلفة المبيعات', 'type' => 'expense'],
            ['code' => '5500', 'name' => 'مصروفات بنكية', 'type' => 'expense'],
        ];

        $codes = array_column($accounts, 'code');
        if (count($codes) !== count(array_unique($codes))) {
            throw new \InvalidArgumentException('Duplicate account codes defined in AccountSeeder.');
        }

        foreach ($accounts as $account) {
            Account::query()->updateOrCreate(['code' => $account['code']], [
                'name' => $account['name'],
                'type' => $account['type'],
                'parent_id' => $parentIds[$account['type']] ?? null,
                'normal_balance' => $account['normal_balance']
                    ?? (in_array($account['type'], ['asset', 'expense'], true) ? 'debit' : 'credit'),
                'is_active' => true,
                'is_system' => true,
                'allow_posting' => $account['allow_posting'] ?? true,
            ]);
        }
    }
}
