<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities;

use App\Exports\IdentityProfilesExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Support\Arr;
use App\Searches\Sponsor\IdentitiesSearch;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class IndexIdentitiesRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'fund_id' => [
                'nullable',
                Rule::in($this->organization->funds->pluck('id')->toArray()),
            ],
            'city' => 'nullable|string',
            'has_bsn' => 'nullable|boolean',
            'postal_code' => 'nullable|string',
            'municipality_name' => 'nullable|string',
            'birth_date_to' => 'nullable|date_format:Y-m-d',
            'birth_date_from' => 'nullable|date_format:Y-m-d',
            'last_login_to' => 'nullable|date_format:Y-m-d',
            'last_login_from' => 'nullable|date_format:Y-m-d',
            'last_activity_to' => 'nullable|date_format:Y-m-d',
            'last_activity_from' => 'nullable|date_format:Y-m-d',
            ...$this->sortableResourceRules(),
            ...$this->exportableResourceRules(
                Arr::pluck(IdentityProfilesExport::getExportFields($this->organization), 'key')
            ),
            ...$this->sortableResourceRules(100, IdentitiesSearch::SORT_BY),
        ];
    }
}
