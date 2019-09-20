<?php

namespace App\Http\Requests\Api\Platform\Products;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestProductRequest extends FormRequest
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
            // 'validator_id' => 'required|exists:validators,id',
            'fund_id' => 'required|exists:funds,id',
            'records' => 'nullable|array',
            'records.*.record_id' => [
                'required',
                Rule::exists('records', 'id')->where(function (Builder $query) {
                    $query->where('identity_address', auth_address());
                }),
            ],
            'records.*.files' => [
                'nullable', 'array'
            ],
            'records.*.files.*' => [
                'required',
                'exists:files,uid',
            ],
        ];
    }
}
