<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreFundRequest
 * @property Organization|null $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
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
        $criteriaEditable = config(
            'forus.features.dashboard.organizations.funds.criteria');
        $formulaProductsEditable = config(
            'forus.features.dashboard.organizations.funds.formula_products');

        if ($this->organization) {
            $availableValidators = $this->organization
                ->employeesOfRoleQuery('validation')->pluck('id')->toArray();
        } else {
            $availableValidators = [];
        }

        return array_merge([
            'name'                          => 'required|between:2,200',
            'description'                   => 'nullable|string|max:140',
            'start_date'                    => 'required|date_format:Y-m-d|after:' . $start_after,
            'end_date'                      => 'required|date_format:Y-m-d|after:start_date',
            'notification_amount'           => 'nullable|numeric',
        ], [
            'auto_requests_validation' => [
                'nullable', 'boolean'
            ],
            'default_validator_employee_id' => [
                'nullable', Rule::in($availableValidators)
            ],
        ], $criteriaEditable ? [
            'criteria'                      => 'required|array',
            'criteria.*.operator'           => 'required|in:=,<,>',
            'criteria.*.record_type_key'    => 'required|exists:record_types,key',
            'criteria.*.value'              => 'required|string|between:1,20',
            'criteria.*.show_attachment'    => 'nullable|boolean',
            'criteria.*.description'        => 'nullable|string|max:4000',
        ] : [], $formulaProductsEditable ? [
            'formula_products'              => 'nullable|array',
            'formula_products.*'            => [
                'required',
                Rule::exists('products', 'id')->where('unlimited_stock', true)
            ],
        ] : []);
    }

    public function attributes()
    {
        return [
            'criteria.*.value' => 'Waarde'
        ];
    }
}
