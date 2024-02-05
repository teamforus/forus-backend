<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Models\RecordType;
use App\Rules\PrevalidationItemHasRequiredKeysRule;
use App\Rules\PrevalidationItemRule;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StorePrevalidationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('store', Prevalidation::class);
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
        $presentKeys = RecordType::query()
            ->whereIn('key', is_array($data) ? array_keys($data) : [])
            ->where('criteria', true)
            ->pluck('key')
            ->toArray();

        return array_merge([
            'fund_id' => [
                'required',
                Rule::in($this->getAvailableFunds()->pluck('id')->toArray())
            ],
            'data' => [
                'required',
                'array',
                new PrevalidationItemHasRequiredKeysRule($fund),
            ],
            'data.*' => 'required'
        ], array_fill_keys([...$this->getRequiredKeysRules($fund, $presentKeys)], [
            'required', new PrevalidationItemRule($fund)
        ]));
    }

    /**
     * @param Fund|null $fund
     * @param array $extraKeys
     * @return array
     */
    private function getRequiredKeysRules(?Fund $fund, array $extraKeys = []): array
    {
        // all required keys must be present and validated by criteria
        return $fund ? array_map(fn ($key) => "data.$key", [
            ...$fund->requiredPrevalidationKeys(),
            ...$extraKeys,
        ]) : [];
    }

    /**
     * @return Builder
     */
    private function getAvailableFunds(): Builder
    {
        return Fund::whereHas('organization', function(Builder $builder) {
            OrganizationQuery::whereHasPermissions($builder, $this->auth_address(), 'validate_records');
        })->where(function(Builder $builder) {
            FundQuery::whereIsInternal($builder);
            FundQuery::whereIsConfiguredByForus($builder);
        })->where('state', '!=', Fund::STATE_CLOSED);
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return RecordType::get()->mapWithKeys(static function(RecordType $recordType) {
            return ["data.$recordType->key" => strtolower($recordType->name)];
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
}
