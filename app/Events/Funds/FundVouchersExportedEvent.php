<?php

namespace App\Events\Funds;

use App\Models\Fund;

class FundVouchersExportedEvent extends BaseFundEvent
{
    protected array $exportDetails;

    /**
     * @param Fund $fund
     * @param array $exportDetails
     */
    public function __construct(Fund $fund, array $exportDetails = [])
    {
        parent::__construct($fund);
        $this->exportDetails = $exportDetails;
    }

    /**
     * @return array
     */
    public function getExportDetails(): array
    {
        return $this->exportDetails;
    }
}