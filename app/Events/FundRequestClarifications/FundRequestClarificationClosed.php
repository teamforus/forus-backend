<?php

namespace App\Events\FundRequestClarifications;

use App\Models\FundRequestClarification;

class FundRequestClarificationClosed extends BaseFundRequestClarificationEvent
{
    protected bool $notifyRequester;

    /**
     * Create a new event instance.
     *
     * @param FundRequestClarification $fundRequestClarification
     * @param bool $notifyRequester
     */
    public function __construct(FundRequestClarification $fundRequestClarification, bool $notifyRequester)
    {
        parent::__construct($fundRequestClarification);
        $this->notifyRequester = $notifyRequester;
    }

    /**
     * @return bool
     */
    public function getNotifyRequester(): bool
    {
        return $this->notifyRequester;
    }
}
