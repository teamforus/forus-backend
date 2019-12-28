<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Models\Fund;
use App\Rules\FundRequestRecordRecordTypeKeyRule;
use App\Rules\FundRequestRecordValueRule;
use App\Rules\RecordTypeKeyExistsRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreFundRequestRequest
 * @property Fund|null $fund
 * @package App\Http\Requests\Api\Platform\Funds\Requests
 */
class StoreFundRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->fund->state == Fund::STATE_ACTIVE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
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
                'nullable', 'array'
            ],
            'records.*.files.*' => [
                'required',
                'exists:files,uid',
            ],
        ];
    }
}
