<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Organization;
use App\Models\PrevalidationRecord;
use App\Services\Forus\Record\Models\RecordType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrevalidationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $fundsAvailable = Organization::queryByIdentityPermissions(
            auth()->id(),
            'validate_records'
        )->get()->pluck('funds')->flatten()->filter(function ($fund) {
            return $fund->state != Fund::STATE_CLOSED;
        })->pluck('id');

        $fund = Fund::find(request()->input('fund_id'));
        $isUnique = true;
        $dataRules = [];
        $recordRepo = resolve('forus.services.record');
        $recordTypes = collect($recordRepo->getRecordTypes())->keyBy('key');

        if ($fund) {
            $isUnique = PrevalidationRecord::where(function (
                Builder $builder
            ) use ($fund, $recordRepo) {
                $builder->where([
                    'record_type_id' => $recordRepo->getTypeIdByKey(
                        $fund->fund_config->csv_primary_key
                    ),
                    'value' => $this->input('data.' . $fund->fund_config->csv_primary_key)
                ])->whereHas('prevalidation', function(
                    Builder $builder
                ) use ($fund)  {
                    $builder->where([
                        'identity_address' => auth()->id(),
                        'fund_id' => $fund->id
                    ]);
                });
            })->doesntExist();

            $dataRules = $fund->criteria->mapWithKeys(function(
                FundCriterion $fundCriterion
            ) use ($recordRepo, $recordTypes) {
                $recordType = $recordTypes[$fundCriterion->record_type_key];
                $typeKey = $recordType['type'];

                $operators = [
                    '<'     => 'lt:',
                    '<='    => 'lte:',
                    '='     => 'in:',
                    '>'     => 'gt:',
                    '>='    => 'gte:',
                ];

                $dataTypes = [
                    'number' => 'numeric',
                    'string' => 'string',
                ];

                if ($typeKey == 'number') {
                    $operatorRule = $operators[$fundCriterion->operator] . $fundCriterion->value;
                } elseif ($typeKey == 'string') {
                    $operatorRule = Rule::in($fundCriterion->value);
                } else {
                    $operatorRule = null;
                }

                return [
                    'data.' . $fundCriterion->record_type_key => [
                        'required',
                        $dataTypes[$typeKey] ?? $typeKey['string'],
                        $operatorRule,
                    ]
                ];
            })->toArray();
        }

        return array_merge([
            'fund_id' => [
                'required',
                Rule::in($fundsAvailable)
            ],
            'data' => [
                'required',
                'array',
            ]
        ], $fund ? $fund->requiredPrevalidationKeys()->mapWithKeys(function(
            $key
        ) use ($fund, $isUnique, $dataRules) {
            return array_merge([
                'data.' . $key => [
                    'required',
                    $fund->fund_config->csv_primary_key == $key && !$isUnique ? (
                    $isUnique ? null : Rule::in([])
                    ) : null
                ]
            ], $dataRules);
        })->toArray() : []);
    }

    public function attributes()
    {
        return RecordType::get()->mapWithKeys(function(RecordType $recordType) {
            return [
                'data.' . $recordType->key => strtolower($recordType->name)
            ];
        })->toArray();
    }

    public function messages()
    {
        $fund = Fund::find(request()->input('fund_id'));

        if (!$fund) {
            return parent::messages();
        }

        return [
            'data.' . $fund->fund_config->csv_primary_key . '.in' => trans('validation.unique')
        ];
    }
}
