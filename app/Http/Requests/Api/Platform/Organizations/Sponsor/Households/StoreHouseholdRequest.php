<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Households;

use App\Http\Requests\BaseFormRequest;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 * @property-read Household|null $household
 */
class StoreHouseholdRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     *  Returns the validation rules for the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->baseRules(true);
    }

    /**
     * Returns the base validation rules for household address information.
     *
     * @param bool $isStore Determines if the rules are for creation or update operations
     * @return array
     */
    protected function baseRules(bool $isStore): array
    {
        return [
            'uid' => [
                $isStore ? 'required' : 'sometimes',
                'string', 'max:20',
                Rule::unique('households', 'uid')
                    ->ignore($isStore ? null : $this->household?->id)
                    ->where('organization_id', $this->organization->id),
            ],
            'living_arrangement' => [
                $isStore ? 'nullable' : 'sometimes',
                Rule::in(Household::LIVING_ARRANGEMENTS),
            ],
            'count_people' => ['nullable', 'integer'],
            'count_minors' => ['nullable', 'integer'],
            'count_adults' => ['nullable', 'integer'],
            'city' => ['nullable', 'string'],
            'street' => ['nullable', 'string'],
            'house_nr' => ['nullable', 'string'],
            'house_nr_addition' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'neighborhood_name' => ['nullable', 'string'],
            'municipality_name' => ['nullable', 'string'],
        ];
    }
}
