<?php

namespace App\Events\FundRequests;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class BaseFundRequestEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param FundRequest $fundRequest
     * @param Employee|null $employee
     * @param Employee|null $supervisorEmployee
     * @param array|null $responseData
     */
    public function __construct(
        protected FundRequest $fundRequest,
        protected ?Employee $employee = null,
        protected ?Employee $supervisorEmployee = null,
        protected ?array $responseData = null,
    ) {
    }

    /**
     * Get the fund request.
     *
     * @return FundRequest
     */
    public function getFundRequest(): FundRequest
    {
        return $this->fundRequest;
    }

    /**
     * Get the fund request.
     *
     * @return Fund
     */
    public function getFund(): Fund
    {
        return $this->fundRequest->fund;
    }

    /**
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    /**
     * @return Employee|null
     */
    public function getSupervisorEmployee(): ?Employee
    {
        return $this->supervisorEmployee;
    }

    /**
     * @return array
     */
    public function getResponseArray(): array
    {
        return $this->responseData ? [
            'fund_request_prefill_response_code' => Arr::get($this->responseData, 'code'),
            'fund_request_prefill_response_body' => Str::limit(json_encode(Arr::get($this->responseData, 'body')), 16384),
        ] : [];
    }
}
