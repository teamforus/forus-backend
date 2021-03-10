<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers;

use App\Http\Requests\Api\Platform\Organizations\IndexOrganizationRequest;
use App\Models\Organization;

/**
 * Class IndexProvidersRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers
 */
class IndexProvidersRequest extends IndexOrganizationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'q' => 'nullable|string|max:100',
            'fund_id' => 'nullable|in:' . $this->organization->funds()->pluck('id')->join(',')
        ]);
    }
}
