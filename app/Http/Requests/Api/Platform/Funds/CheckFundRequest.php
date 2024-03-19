<?php

namespace App\Http\Requests\Api\Platform\Funds;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Traits\ThrottleWithMeta;
use Illuminate\Support\Facades\Config;

class CheckFundRequest extends BaseFormRequest
{
    use ThrottleWithMeta;
}
