<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests\Records;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use App\Rules\FundRequests\Sponsor\FundRequestRecordValueSponsorRule;

/**
 * @property-read Organization $organization
 * @property-read FundRequestRecord $fund_request_record
 */
class UpdateFundRequestRecordRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (FundRequestRecordValueSponsorRule|string)[][]
     *
     * @psalm-return array{value: list{'required', FundRequestRecordValueSponsorRule}}
     */
    public function rules(): array
    {
        return [
            'value' => [
                'required',
                new FundRequestRecordValueSponsorRule(
                    $this->fund_request_record->fund_request->fund,
                    $this,
                    $this->fund_request_record->fund_criterion,
                ),
            ],
        ];
    }
}
