<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundProviderInvitation;

class FundProviderInvitedEvent extends BaseFundEvent
{
    protected $fundProviderInvitation;

    /**
     * @return FundProviderInvitation
     */
    public function getFundProviderInvitation(): FundProviderInvitation
    {
        return $this->fundProviderInvitation;
    }
}
