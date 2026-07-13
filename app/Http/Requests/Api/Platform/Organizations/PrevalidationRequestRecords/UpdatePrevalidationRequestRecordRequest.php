<?php

namespace App\Http\Requests\Api\Platform\Organizations\PrevalidationRequestRecords;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestRecord;
use App\Rules\FundRequests\Sponsor\PrevalidationRequestRecordValueSponsorRule;

/**
 * @property-read Organization $organization
 * @property-read PrevalidationRequest $prevalidation_request
 * @property-read PrevalidationRequestRecord $record
 */
class UpdatePrevalidationRequestRecordRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'value' => [
                'required',
                new PrevalidationRequestRecordValueSponsorRule(
                    $this->prevalidation_request->fund,
                    $this,
                    $this->record,
                    $this->prevalidation_request,
                ),
            ],
        ];
    }
}
