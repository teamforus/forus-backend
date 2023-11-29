<?php

namespace App\Events\FundRequestClarifications;

use App\Models\FundRequestClarification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class BaseFundRequestClarificationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected FundRequestClarification $fundRequestClarification;

    /**
     * Create a new event instance.
     *
     * @param FundRequestClarification $fundRequestClarification
     */
    public function __construct(FundRequestClarification $fundRequestClarification)
    {
        $this->fundRequestClarification = $fundRequestClarification;
    }

    /**
     * Get the fund request
     *
     * @return FundRequestClarification
     */
    public function getFundRequestClarification(): FundRequestClarification
    {
        return $this->fundRequestClarification;
    }
}
