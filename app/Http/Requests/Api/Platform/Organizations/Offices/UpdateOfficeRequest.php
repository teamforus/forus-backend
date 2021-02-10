<?php

namespace App\Http\Requests\Api\Platform\Organizations\Offices;

/**
 * Class UpdateOfficeRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Offices
 */
class UpdateOfficeRequest extends BaseOfficeRequest
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
