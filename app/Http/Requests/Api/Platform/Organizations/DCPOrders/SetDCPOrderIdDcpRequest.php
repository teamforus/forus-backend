<?php

namespace App\Http\Requests\Api\Platform\Organizations\DCPOrders;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class SetDCPOrderIdDcpRequest extends BaseFormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'order_id_dcp' => [
                'required',
                'string',
                'max:100',
                Rule::unique('dcp_orders', 'order_id_dcp'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'order_id_dcp' => trans('dcp.attributes.order_id_dcp'),
        ];
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'order_id_dcp' => trim((string) $this->input('order_id_dcp')),
        ]);
    }
}
