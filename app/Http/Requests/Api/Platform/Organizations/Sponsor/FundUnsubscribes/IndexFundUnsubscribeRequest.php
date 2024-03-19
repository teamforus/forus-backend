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
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array<string>
     */
    public function rules(): array
    {
        return FundUnsubscribeSearch::rules($this);
    }
}
