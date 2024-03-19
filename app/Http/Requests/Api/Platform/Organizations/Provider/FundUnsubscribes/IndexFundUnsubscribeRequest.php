<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes;

use App\Http\Requests\BaseFormRequest;
use App\Searches\FundUnsubscribeSearch;

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
