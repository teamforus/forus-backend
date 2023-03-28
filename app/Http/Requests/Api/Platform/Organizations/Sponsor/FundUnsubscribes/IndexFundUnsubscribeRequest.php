<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\FundUnsubscribes;

use App\Http\Requests\BaseFormRequest;
use App\Models\Voucher;
use App\Searches\FundUnsubscribeSearch;

/**
 * @property-read Voucher $voucher
 */
class IndexFundUnsubscribeRequest extends BaseFormRequest
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
        return FundUnsubscribeSearch::rules($this);
    }
}
