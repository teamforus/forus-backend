<?php

namespace App\Services\BackofficeApiService\Responses;

use App\Models\FundBackofficeLog;
use App\Services\BackofficeApiService\BackofficeApi;

class EligibilityResponse
{
    protected FundBackofficeLog $log;

    /**
     * @param FundBackofficeLog $log
     */
    public function __construct(FundBackofficeLog $log)
    {
        $this->log = $log;
    }

    /**
     * @return bool
     */
    public function isEligible(): bool
    {
        return
            $this->log->state === BackofficeApi::STATE_SUCCESS &&
            $this->log->response_body['eligible'] ?? false;
    }

    /**
     * @return FundBackofficeLog
     */
    public function getLog(): FundBackofficeLog
    {
        return $this->log;
    }
}
