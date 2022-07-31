<?php

namespace App\Events\Funds;

use App\Models\Fund;
use Illuminate\Database\Eloquent\Collection;

class FundVouchersExportEvent extends BaseFundEvent
{
    protected Collection $vouchers;

    /**
     * @param Fund $fund
     * @param Collection $vouchers
     */
    public function __construct(Fund $fund, Collection $vouchers)
    {
        parent::__construct($fund);
        $this->vouchers = $vouchers;
    }

    /**
     * @return Collection
     */
    public function getVouchers(): Collection
    {
        return $this->vouchers;
    }
}