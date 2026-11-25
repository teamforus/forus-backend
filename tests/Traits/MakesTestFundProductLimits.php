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
     * @param int|float $limit
     * @param array $productIds
     * @param string $limitBy
     * @return FundProductLimit
     */
    public function makeFundProductLimit(
        Fund $fund,
        string $type = FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED,
        int|float $limit = 1,
        array $productIds = [],
        string $limitBy = FundProductLimit::LIMIT_BY_PRODUCTS,
    ): FundProductLimit {
        $fundProductLimit = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => $type,
            'limit_by' => $limitBy,
            'limit' => $limit,
        ]);

        $fundProductLimit->products()->sync($productIds);

        return $fundProductLimit;
    }
}
