<?php

namespace App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Models\RecordType;
use App\Rules\PrevalidationDataItemRule;
use App\Rules\PrevalidationDataRule;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class UploadPrevalidationRequestsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [PrevalidationRequest::class, $this->organization]);
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
                new PrevalidationDataRule($fund, true),
            ],
            'data.*' => [
                'required',
                'array',
                'min:1',
            ],
            'data.*.bsn' => [
                'required',
                'distinct',
                Rule::unique('prevalidation_requests', 'bsn')->where('fund_id', $fund->id),
                ...$this->bsnRules(),
            ],
            'data.*.*' => [
                new PrevalidationDataItemRule($recordTypes, $fund, $data),
            ],
            ...$this->requiredGroupsRules($recordTypes, $fund, $data),
            ...$this->uploadedCSVFileRules(),
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return ['data.*.bsn' => 'BSN'];
    }

    /**
     * @param Collection $recordTypes
     * @param Fund $fund
     * @param array $data
     * @return array
     */
    private function requiredGroupsRules(Collection $recordTypes, Fund $fund, array $data): array
    {
        $rules = [];

        $groups = $fund->criteria_groups()
            ->where('required', true)
            ->get();

        foreach ($groups as $group) {
            foreach ($data as $index => $datum) {
                $groupRecordTypeKeys = $group->fund_criteria->pluck('record_type_key')->all();
                $groupSubmittedValues = array_intersect_key(array_keys($datum), array_flip($groupRecordTypeKeys));

                if (!array_filter($groupSubmittedValues, fn ($value) => !empty($value))) {
                    $criterion = $group->fund_criteria->first();

                    $rules = [
                        ...$rules,
                        "data.$index.$criterion->record_type_key" => [
                            'required',
                            new PrevalidationDataItemRule($recordTypes, $fund, $data),
                        ],
                    ];
                }
            }
        }

        return $rules;
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
