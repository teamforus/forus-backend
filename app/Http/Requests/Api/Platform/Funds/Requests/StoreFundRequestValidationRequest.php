<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

class StoreFundRequestValidationRequest extends StoreFundRequestRequest
{
    protected bool $isValidationRequest = true;
}
