<?php

namespace Tests\Browser\Exports;

use Tests\Browser\Traits\ExportsFundsStatisticsTrait;
use Tests\DuskTestCase;
use Throwable;

class FundsStatisticsExportTest extends DuskTestCase
{
    use ExportsFundsStatisticsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundsStatisticsExport(): void
    {
        $this->doTestExportFundFinancialStatistics(false);
    }
}
