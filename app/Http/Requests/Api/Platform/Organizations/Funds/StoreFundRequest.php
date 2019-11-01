<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use Illuminate\Foundation\Http\FormRequest;

class StoreFundRequest extends FormRequest
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
        $start_after = now()->addDays(5)->format('Y-m-d');
        $criteriaEditable = config('forus.features.dashboard.organizations.funds.criteria');

        return array_merge([
            'name'                          => 'required|between:2,200',
            'start_date'                    => 'required|date_format:Y-m-d|after:' . $start_after,
            'end_date'                      => 'required|date_format:Y-m-d|after:start_date',
            'product_categories'            => 'present|array',
            'product_categories.*'          => 'exists:product_categories,id',
            'notification_amount'           => 'nullable|numeric',
        ], $criteriaEditable ? [
            'criteria'                      => 'required|array',
            'criteria.*.operator'           => 'required|in:=,<,>',
            'criteria.*.record_type_key'    => 'required|exists:record_types,key',
            'criteria.*.value'              => 'required|string|between:1,10',
        ] : []);
    }

    public function attributes()
    {
        return [
            'criteria.*.value' => 'Waarde'
        ];
    }
}
