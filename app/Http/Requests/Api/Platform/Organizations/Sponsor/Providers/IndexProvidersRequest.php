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
        $funds = $this->organization->funds->pluck('id');

        return array_merge(parent::rules(), [
            'fund_ids'          => 'nullable|array',
            'fund_ids.*'        => 'required|exists:funds,id|in:' . $funds->join(','),
            'postcodes'         => 'nullable|array',
            'postcodes.*'      => 'nullable|string|max:100',
            'provider_ids'      => 'nullable|array',
            'provider_ids.*'    => 'nullable|exists:organizations,id',
            'product_category_ids'   => 'nullable|array',
            'product_category_ids.*' => 'nullable|exists:product_categories,id',
        ]);
    }
}
