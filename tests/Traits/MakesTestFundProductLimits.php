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
    public function makeFundProductLimit(
        Fund $fund,
        string $type = FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED,
        int $limit = 1,
        array $productIds = [],
    ): FundProductLimit {
        $fundProductLimit = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => $type,
            'limit' => $limit,
        ]);

        $fundProductLimit->products()->sync($productIds);

        return $fundProductLimit;
    }
}
