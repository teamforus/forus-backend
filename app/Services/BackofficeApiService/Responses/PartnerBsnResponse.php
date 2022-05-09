<?php

namespace App\Services\BackofficeApiService\Responses;

use App\Models\FundBackofficeLog;

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
     * @return string|null
     */
    public function getBsn(): ?string
    {
        return $this->log->response_body['partner_bsn'] ?? null;
    }

    /**
     * @return FundBackofficeLog
     */
    public function getLog(): FundBackofficeLog
    {
        return $this->log;
    }
}