<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Rules\FundRequestRecordFileRule;
use App\Rules\FundRequestRecordRecordTypeKeyRule;
use App\Rules\FundRequestRecordValueRule;
use App\Rules\RecordTypeKeyExistsRule;
use Illuminate\Validation\Rule;

/**
 * Class StoreFundRequestRequest
 * @property Fund|null $fund
 * @package App\Http\Requests\Api\Platform\Funds\Requests
 */
class StoreFundRequestRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->fund->state === Fund::STATE_ACTIVE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fund = $this->fund;
        $criteria = $fund ? $fund->criteria()->pluck('id')->toArray() : [];

        return [
            'records' => [
                'present',
                'array',
                'min:1',
            ],
            'records.*.value' => [
                'required',
                $fund ? new FundRequestRecordValueRule($this, $fund) : '',
            ],
            'records.*.fund_criterion_id' => [
                'required',
                Rule::in($criteria),
            ],
            'records.*.record_type_key' => [
                'required',
                'not_in:primary_email',
                new RecordTypeKeyExistsRule(),
                $fund ? new FundRequestRecordRecordTypeKeyRule($this, $fund) : ''
            ],
            'records.*.files' => [
                'array',
                new FundRequestRecordFileRule($this, $fund),
            ]
        ];
    }

    /**
     * @return array
     */
    public function messages(): array {
        $recordRepo = resolve('forus.services.record');
        $messages = [];

        foreach ($this->get('records') as $key => $val) {
            if (isset($val['record_type_key'])) {
                $recordTypeName = collect(
                    $recordRepo->getRecordTypes()
                )->firstWhere('key', $val['record_type_key'])['name'] ?? '';

                $messages["records.*.value.required"] = trans('validation.required', [
                    'attribute' => $recordTypeName
                ]);
            }
        }

        return $messages;
    }
}
