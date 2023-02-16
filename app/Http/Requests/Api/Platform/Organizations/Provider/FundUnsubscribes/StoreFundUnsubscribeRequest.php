<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes;

use App\Http\Requests\BaseFormRequest;

class StoreFundUnsubscribeRequest extends BaseFormRequest
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
            'unsubscribe_date'  => 'date|date_format:Y-m-d|after:today',
            'fund_provider_id'  => 'nullable|exists:fund_providers,id',
            'note'              => 'nullable|string|max:1000',
        ];
    }
}
