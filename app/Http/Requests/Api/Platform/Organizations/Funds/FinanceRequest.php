<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use Illuminate\Foundation\Http\FormRequest;

class FinanceRequest extends FormRequest
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
        $type = $this->input('type');

        return [
            'type'              => 'required|in:year,quarter,month,week,day',
            'type_value'        => $this->typeValueRule($type),
            'fund_ids'          => 'nullable|array',
            'fund_ids.*'        => 'required|exists:funds,id',
            'postcodes'         => 'nullable|array',
            'postcodes.*'       => 'nullable|string|max:100',
            'provider_ids'      => 'nullable|array',
            'provider_ids.*'    => 'nullable|exists:organizations,id',
            'product_category_ids'   => 'nullable|array',
            'product_category_ids.*' => 'nullable|exists:product_categories,id',
        ];
    }

    /**
     * @param string|null $type
     * @return string
     */
    private function typeValueRule(?string $type): string
    {
        switch ($type) {
            case 'year': return 'required|date_format:Y';
            case 'quarter': return 'required|in:q1,q2,q3,q4';
            case 'month':
            case 'week':
            case 'day': return 'required|in:';
        }

        return '';
    }
}
