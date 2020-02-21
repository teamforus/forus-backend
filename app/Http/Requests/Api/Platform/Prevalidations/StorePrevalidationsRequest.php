<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Models\Fund;
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

        if ($fund) {
            $isUnique = PrevalidationRecord::where(function (
                Builder $builder
            ) use ($fund) {
                $recordRepo = resolve('forus.services.record');

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
        ) use ($fund, $isUnique) {
            return [
                'data.' . $key => [
                    'required',
                    $fund->fund_config->csv_primary_key == $key && !$isUnique ? (
                        $isUnique ? null : Rule::in([])
                    ) : null
                ]
            ];
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
