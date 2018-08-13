<?php

namespace App\Http\Requests\Api\Platform\ValidatorRequest;

use App\Rules\ValidatorRequestNotPendingRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreValidatorRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'record_id' => [
                'required',
                Rule::exists('records', 'id')->where(function (Builder $query) {
                    $query->where([
                    'identity_address' => request()->get('identity')
                    ]);
                }),
                new ValidatorRequestNotPendingRule(request()->input('validator_id'))
            ],
            'validator_id' => 'required|exists:validators,id',
        ];
    }
}
