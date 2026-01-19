<?php

namespace App\Events\FundRequests;

use App\Models\FundRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FundRequestCreated extends BaseFundRequestEvent
{
    /**
     * @param FundRequest $fundRequest
     * @param array|null $iConnectResponseData
     */
    public function __construct(
        protected FundRequest $fundRequest,
        protected ?array $iConnectResponseData = null,
    ) {
        parent::__construct($fundRequest);
    }

    /**
     * @return array
     */
    public function getIConnectResponseArray(): array
    {
        return $this->iConnectResponseData ? [
            'fund_request_prefill_response_code' => Arr::get($this->iConnectResponseData, 'code'),
            'fund_request_prefill_response_body' => Str::limit(json_encode(Arr::get($this->iConnectResponseData, 'body')), 16384),
        ] : [];
    }
}
