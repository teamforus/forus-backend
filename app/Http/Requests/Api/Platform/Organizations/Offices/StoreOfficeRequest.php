<?php

namespace App\Http\Requests\Api\Platform\Organizations\Offices;

/**
 * Class StoreOfficeRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Offices
 */
class StoreOfficeRequest extends BaseOfficeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->updateRules();
    }
}
