<?php

namespace Tests\Browser\Exports;

use Tests\Browser\Traits\ExportsFundsStatisticsTrait;
use Tests\DuskTestCase;
use Throwable;

class FundsStatisticsBudgetExportTest extends DuskTestCase
{
    use ExportsFundsStatisticsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundStatisticsBudgetExport(): void
    {
        $this->doTestExportFundFinancialStatistics(true);
    }
}
