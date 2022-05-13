<?php

namespace App\Services\BackofficeApiService\Responses;

use App\Models\FundBackofficeLog;
use App\Services\BackofficeApiService\BackofficeApi;

class PartnerBsnResponse
{
    protected FundBackofficeLog $log;

    /**
     * ResidencyResponse constructor.
     * @param FundBackofficeLog $log
     */
    public function __construct(FundBackofficeLog $log)
    {
        $this->log = $log;
    }

    /**
     * @return string|false|null
     */
    public function getBsn()
    {
        if ($this->log->state === BackofficeApi::STATE_SUCCESS) {
            return $this->log->response_body['partner_bsn'] ?? false;
        }

        return null;
    }

    /**
     * @return FundBackofficeLog
     */
    public function getLog(): FundBackofficeLog
    {
        return $this->log;
    }
}