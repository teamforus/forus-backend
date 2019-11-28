<?php

namespace App\Http\Requests\DigID;

use Illuminate\Foundation\Http\FormRequest;

class StartDigIdRequest extends FormRequest
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
            'target' => [
                'nullable',
                'alpha_dash',
            ],
            'fund_id' => [
                'required',
                'exists:funds,id',
            ]
        ];
    }
}
