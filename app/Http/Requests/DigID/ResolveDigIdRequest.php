<?php

namespace App\Http\Requests\DigID;

use App\Http\Requests\BaseFormRequest;

/**
 * Class ResolveDigIdRequest
 * @package App\Http\Requests\DigID
 */
class ResolveDigIdRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     *
     * @psalm-return array<never, never>
     */
    public function rules(): array
    {
        return [];
    }
}
