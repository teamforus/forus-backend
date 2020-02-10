<?php

namespace App\Http\Requests\Api\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class SmsRequest
 * @package App\Http\Requests\Api\Platform
 */
class SmsRequest extends FormRequest
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
            'type' => [
                'required', Rule::in([
                    'me_app_download_link'
                ])
            ],
            'phone' => [
                'required', "starts_with:+31"
            ]
        ];
    }
}
