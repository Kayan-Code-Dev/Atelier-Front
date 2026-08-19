<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Account;
use App\Models\Tenant\FixedAssetCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

class FixedAssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $assetByCode = static fn (string $code): int => self::accountId($code);
        $accum = $assetByCode('1490');
        $expense = $assetByCode('5300');
        $gainLoss = $assetByCode('4300');

        $rows = [
            ['code' => 'FURN', 'name' => 'أثاث', 'asset' => '1410'],
            ['code' => 'EQPT', 'name' => 'معدات', 'asset' => '1420'],
            ['code' => 'COMP', 'name' => 'أجهزة كمبيوتر', 'asset' => '1430'],
            ['code' => 'ELEC', 'name' => 'أجهزة كهربائية', 'asset' => '1440'],
            ['code' => 'VEHL', 'name' => 'سيارات', 'asset' => '1450'],
            ['code' => 'FITT', 'name' => 'تجهيزات', 'asset' => '1460'],
            ['code' => 'OTHR', 'name' => 'أخرى', 'asset' => '1410'],
        ];

        foreach ($rows as $row) {
            FixedAssetCategory::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => null,
                    'asset_account_id' => $assetByCode($row['asset']),
                    'accumulated_depreciation_account_id' => $accum,
                    'depreciation_expense_account_id' => $expense,
                    'disposal_gain_loss_account_id' => $gainLoss,
                    'active' => true,
                ]
            );
        }
    }

    private static function accountId(string $code): int
    {
        $account = Account::query()->where('code', $code)->first();
        if ($account === null) {
            throw new RuntimeException("Required account [{$code}] is missing. Seed the chart of accounts first.");
        }

        return (int) $account->id;
    }
}
