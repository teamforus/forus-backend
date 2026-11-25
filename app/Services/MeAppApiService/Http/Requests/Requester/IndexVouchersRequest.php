<?php

namespace App\Services\MeAppApiService\Http\Requests\Requester;

use App\Http\Requests\Api\Platform\Vouchers\IndexVouchersRequest as LegacyIndexVouchersRequest;
use Illuminate\Support\Arr;

class IndexVouchersRequest extends LegacyIndexVouchersRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['product_id']);
    }
}
