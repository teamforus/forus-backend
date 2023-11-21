<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests\Records;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Rules\BsnRule;

/**
 * @property-read Organization $organization
 */
class StoreFundRequestRecordRequest extends BaseFormRequest
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
            'value' => ['required', new BsnRule()],
            'record_type_key' => 'required|in:' . ($this->organization->bsn_enabled ? 'partner_bsn' : ''),
        ];
    }
}
