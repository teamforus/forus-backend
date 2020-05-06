<?php

namespace App\Http\Requests\Api\Platform\Organizations\Offices;

use App\Rules\Base\ScheduleRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexOfficeRequest extends FormRequest
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
            'per_page' => [
                'nullable',
                'numeric',
                'max:100'
            ],
        ];
    }
}
