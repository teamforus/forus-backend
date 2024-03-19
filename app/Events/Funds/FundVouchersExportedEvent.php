<?php

namespace App\Events\Funds;

use App\Models\Fund;

class FundVouchersExportedEvent extends BaseFundEvent
{
    protected array $exportDetails;

    /**
     * @return array
     */
    public function getExportDetails(): array
    {
        return $this->exportDetails;
    }
}