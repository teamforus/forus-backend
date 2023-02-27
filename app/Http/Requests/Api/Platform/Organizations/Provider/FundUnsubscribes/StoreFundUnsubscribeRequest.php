<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\FundUnsubscribes;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class StoreFundUnsubscribeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'note'              => 'nullable|string|max:2000',
            'unsubscribe_at'    => 'required|date_format:Y-m-d|after:today',
            'fund_provider_id'  => $this->fundProviderIdRule(),
        ];
    }

    /**
     * @return array
     */
    public function fundProviderIdRule(): array
    {
        $fundProviders = FundProvider::queryActive($this->organization)
            ->whereDoesntHave('fund_unsubscribes_active')
            ->pluck('id')
            ->toArray();

        return [
            'required',
            Rule::in($fundProviders),
        ];
    }
}
