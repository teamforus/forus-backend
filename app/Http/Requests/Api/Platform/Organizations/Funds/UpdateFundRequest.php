<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateFundRequest
 * @property null|Fund $fund
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class UpdateFundRequest extends FormRequest
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
        return array_merge([
            'name'                  => 'required|between:2,200',
            'product_categories'    => 'present|array',
            'product_categories.*'  => 'exists:product_categories,id',
            'notification_amount'   => 'nullable|numeric'
        ], ($this->fund && $this->fund->state == Fund::STATE_WAITING) ? [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after:' . $this->fund->created_at->addDays(5)->format('Y-m-d')
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after:start_date'
            ],
        ] : []);
    }
}
