<?php


namespace App\Services\BackofficeApiService\Responses;


use App\Models\FundBackofficeLog;
use App\Services\BackofficeApiService\BackofficeApi;

/**
 * Class ResidencyResponse
 * @package App\Services\BackofficeApiService\Responses
 */
class ResidencyResponse
{
    protected $log;

    /**
     * ResidencyResponse constructor.
     * @param FundBackofficeLog $log
     */
    public function __construct(FundBackofficeLog $log)
    {
        $this->log = $log;
    }

    /**
     * @return bool
     */
    public function isResident(): bool
    {
        return $this->log->state === BackofficeApi::STATE_SUCCESS &&
            $this->log->response_body['resident'] ?? false;
    }

    /**
     * @return FundBackofficeLog
     */
    public function getLog(): FundBackofficeLog
    {
        return $this->log;
    }
}