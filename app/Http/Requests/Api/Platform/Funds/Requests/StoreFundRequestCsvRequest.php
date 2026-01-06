<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Models\Fund;

class StoreFundRequestCsvRequest extends StoreFundRequestRequest
{
    protected bool $isValidationRequest = false;
    protected bool $isCsvRequest = true;

    /**
     * Get the validation rules that apply to the request.
     *
     * @param Fund $fund
     * @param array $records
     * @return array
     */
    public function csvRules(Fund $fund, array $records): array
    {
        return [
            ...$this->recordsRule($fund, $records),
        ];
    }
}
