<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundUnsubscribe;

class UpdateFundUnsubscribeRequest extends BaseFormRequest
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
            'state' => 'nullable|in:'.FundUnsubscribe::STATE_CANCELED,
        ];
    }
}
