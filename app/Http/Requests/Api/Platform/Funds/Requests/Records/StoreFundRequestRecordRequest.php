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
     * Get the validation rules that apply to the request.
     *
     * @return ((BsnRule|string)[]|string)[]
     *
     * @psalm-return array{value: list{'required', BsnRule}, record_type_key: 'required|in:'|'required|in:partner_bsn'}
     */
    public function rules(): array
    {
        return [
            'value' => ['required', new BsnRule()],
            'record_type_key' => 'required|in:' . ($this->organization->bsn_enabled ? 'partner_bsn' : ''),
        ];
    }
}
