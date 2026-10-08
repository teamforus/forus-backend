<?php

namespace App\Events\FundRequestClarifications;

use App\Models\FundRequestClarification;

class FundRequestClarificationUpdated extends BaseFundRequestClarificationEvent
{
    protected bool $notifyRequester;
    protected string $previousQuestion;

    /**
     * Create a new event instance.
     *
     * @param FundRequestClarification $fundRequestClarification
     * @param string $previousQuestion
     * @param bool $notifyRequester
     */
    public function __construct(
        FundRequestClarification $fundRequestClarification,
        string $previousQuestion,
        bool $notifyRequester,
    ) {
        parent::__construct($fundRequestClarification);
        $this->previousQuestion = $previousQuestion;
        $this->notifyRequester = $notifyRequester;
    }

    /**
     * @return bool
     */
    public function getNotifyRequester(): bool
    {
        return $this->notifyRequester;
    }

    /**
     * @return string
     */
    public function getPreviousQuestion(): string
    {
        return $this->previousQuestion;
    }
}
