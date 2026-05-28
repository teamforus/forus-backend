<?php

namespace App\Http\Requests\Api\Platform\Organizations\FundProductLimits;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundProductLimit;
use App\Models\Organization;
use App\Rules\SponsorProductIdRule;
use App\Scopes\Builders\FundQuery;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class StoreFundProductLimitsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [FundProductLimit::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fundsIds = $this->getAvailableFunds()->pluck('id')->toArray();

        return [
            'fund_id' => [
                'required',
                Rule::in($fundsIds),
            ],
            'state' => [
                'required',
                Rule::in([FundProductLimit::STATE_ACTIVE, FundProductLimit::STATE_INACTIVE]),
            ],
            'type' => [
                'required',
                Rule::in([FundProductLimit::TYPE_ALL, FundProductLimit::TYPE_SELECTED]),
            ],
            'limit' => [
                'required',
                'integer',
                'min:1',
            ],
            'products' => [
                'required_if:type,' . FundProductLimit::TYPE_SELECTED,
                'array',
            ],
            'products.*' => [
                'required',
                new SponsorProductIdRule($fundsIds),
            ],
        ];
    }

    /**
     * @return Builder|Relation|Fund[]
     */
    private function getAvailableFunds(): Builder|Relation|Arrayable
    {
        return $this->organization->funds()
            ->where(function (Builder $builder) {
                FundQuery::whereIsInternal($builder);
                FundQuery::whereIsConfiguredByForus($builder);
            })
            ->where('state', '!=', Fund::STATE_CLOSED);
    }
}
