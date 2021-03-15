<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers;

use App\Http\Requests\Api\Platform\Organizations\Provider\IndexFundProviderRequest;
use App\Models\Organization;

/**
 * Class IndexProvidersRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers
 */
class IndexProvidersRequest extends IndexFundProviderRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules());
    }
}
