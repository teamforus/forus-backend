<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests\Records;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;

/**
 * @property-read Organization $organization
 * @property-read FundRequestRecord $fund_request_record
 */
class UpdateFundRequestRecordRequest extends BaseFormRequest
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
        if ($this->fund_request_record->record_type->type == 'number') {
            return [
                'value' => 'required|numeric',
            ];
        }

        return [
            'value' => 'required|string|max:160',
        ];
    }
}
