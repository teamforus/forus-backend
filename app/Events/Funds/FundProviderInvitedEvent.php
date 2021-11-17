<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundProviderInvitation;

class FundProviderInvitedEvent extends BaseFundEvent
{
    protected $fundProviderInvitation;

    /**
     * Create a new event instance.
     *
     * @param Fund $fund
     * @param FundProviderInvitation $fundProviderInvitation
     */
    public function __construct(Fund $fund, FundProviderInvitation $fundProviderInvitation)
    {
        parent::__construct($fund);
        $this->fundProviderInvitation = $fundProviderInvitation;
    }

    /**
     * @return FundProviderInvitation
     */
    public function getFundProviderInvitation(): FundProviderInvitation
    {
        return $this->fundProviderInvitation;
    }
}
