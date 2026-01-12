<?php

namespace Tests\Unit;

use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordValueRule;
use Tests\CreatesApplication;
use Tests\TestCase;

class FundRequestRecordValueRuleTest extends TestCase
{
    use CreatesApplication;

    /**
     * @return void
     */
    public function testValuesIsEqualHandlesNumericValues(): void
    {
        $this->assertTrue(FundRequestRecordValueRuleProxy::valuesIsEqualProxy('14', 14));
        $this->assertTrue(FundRequestRecordValueRuleProxy::valuesIsEqualProxy('14.00', 14));
        $this->assertTrue(FundRequestRecordValueRuleProxy::valuesIsEqualProxy(0, '0'));
        $this->assertFalse(FundRequestRecordValueRuleProxy::valuesIsEqualProxy('14a', 14));
    }
}

class FundRequestRecordValueRuleProxy extends FundRequestRecordValueRule
{
    public static function valuesIsEqualProxy(mixed $a, mixed $b): bool
    {
        return parent::valuesIsEqual($a, $b);
    }
}
