<?php

namespace App\Http\Requests\Api\Platform\Organizations\Prevalidations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\RecordType;
use App\Rules\PrevalidationItemHasRequiredKeysRule;
use App\Rules\PrevalidationItemRule;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class StorePrevalidationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [Prevalidation::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fund = Fund::find($this->input('fund_id'));
        $data = $this->input('data', []);
        $data = array_filter(is_array($data) ? $data : []);

        return [
            'fund_id' => [
                'required',
                Rule::in($this->getAvailableFunds()->pluck('id')->toArray()),
            ],
            'data' => [
                'required',
                'array',
                new PrevalidationItemHasRequiredKeysRule($fund, $data),
            ],
            ...array_fill_keys([...$this->getRequiredKeysRules($fund, $data)], [
                'required', new PrevalidationItemRule($fund, $data, 'data.'),
            ]),
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return RecordType::get()->mapWithKeys(static function (RecordType $recordType) {
            return ["data.$recordType->key" => strtolower($recordType->name ?? '')];
        })->toArray();
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        if ($fund = Fund::find($this->input('fund_id'))) {
            return ["data.{$fund->fund_config->csv_primary_key}.in" => trans('validation.unique')];
        }

        return parent::messages();
    }

    /**
     * @param Fund|null $fund
     * @param array $data
     * @return array
     */
    private function getRequiredKeysRules(?Fund $fund, array $data): array
    {
        // all required keys must be present and validated by criteria
        return $fund ? array_map(fn ($key) => "data.$key", [
            ...$fund->requiredPrevalidationKeys(false, $data),
        ]) : [];
    }

    /**
     * @return Builder|Relation
     */
    private function getAvailableFunds(): Builder|Relation
    {
        return $this->organization->funds()->where(function (Builder $builder) {
            FundQuery::whereIsInternal($builder);
            FundQuery::whereIsConfiguredByForus($builder);
        })->where('state', '!=', Fund::STATE_CLOSED);
    }
}
