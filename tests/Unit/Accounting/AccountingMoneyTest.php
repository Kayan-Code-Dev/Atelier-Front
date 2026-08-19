<?php

namespace Tests\Unit\Accounting;

use App\Accounting\AccountingMoney;
use Tests\TestCase;

class AccountingMoneyTest extends TestCase
{
    public function test_adds_without_floating_drift(): void
    {
        $sum = AccountingMoney::zero();
        $sum = AccountingMoney::add($sum, '0.10');
        $sum = AccountingMoney::add($sum, '0.20');

        $this->assertSame('0.30', $sum);
        $this->assertSame(0, AccountingMoney::cmp($sum, '0.30'));
        $this->assertTrue(AccountingMoney::isZero(AccountingMoney::sub('100.00', '100.00')));
    }
}
