<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\RecordType;
use App\Rules\PrevalidationDataItemRule;
use App\Rules\PrevalidationDataRule;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class UploadPrevalidationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('store', [Prevalidation::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fund = Fund::find($this->input('fund_id'));
        $recordTypes = RecordType::search()->keyBy('key');
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
                new PrevalidationDataRule($fund),
            ],
            'data.*' => [
                'required',
                'array',
                'min:1',
            ],
            'data.*.*' => [
                new PrevalidationDataItemRule($recordTypes, $fund, $data),
            ],
            'overwrite' => 'nullable|array',
            'overwrite.*' => 'required',
            ...$this->uploadedCSVFileRules(),
        ];
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
