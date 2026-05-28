<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundProductLimit;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\WithFaker;

trait MakesTestFundProductLimits
{
    use WithFaker;
    use DoesTesting;

    /**
     * @param Fund $fund
     * @param string $type
     * @param int $limit
     * @param array $productIds
     * @return FundProductLimit
     */
    public function makeFundProductLimit(Fund $fund, string $type = FundProductLimit::TYPE_ALL, int $limit = 1, array $productIds = []): FundProductLimit
    {
        $fundProductLimit = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => $type,
            'limit' => $limit,
        ]);

        $fundProductLimit->updateProducts($productIds);

        return $fundProductLimit;
    }
}
