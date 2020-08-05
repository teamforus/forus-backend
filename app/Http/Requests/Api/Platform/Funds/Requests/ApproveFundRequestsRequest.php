<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Models\FundRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateFundRequestsRequest
 * @property FundRequest $fund_request
 * @package App\Http\Requests\Api\Platform\Funds\FundRequests
 */
class ApproveFundRequestsRequest extends FormRequest
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
        return [
            'note'  => 'nullable|string|between:0,2000'
        ];
    }
}
