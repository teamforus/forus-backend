<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordCriterionIdRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordFilesRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordValueRule;
use Illuminate\Support\Arr;

/**
 * @property Fund $fund
 */
class StoreFundRequestRequest extends BaseFormRequest
{
    protected bool $isValidationRequest = false;

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
        return array_merge(
            $this->contactInformationRule($this->fund),
            $this->recordsRule($this->fund),
        );
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function contactInformationRule(Fund $fund): array
    {
        $isEnabled = $fund->fund_config->contact_info_enabled;
        $isRequired = $fund->fund_config->contact_info_required;
        $emailIsKnown = !empty($this->identity()->email);

        return [
            'contact_information' => $isEnabled && !$emailIsKnown ? [
                !$this->isValidationRequest && $isRequired ? 'required' : 'nullable',
                'string',
                'min:5',
                'max:2000',
            ] : [],
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function recordsRule(Fund $fund): array
    {
        return [
            'records' => $this->isValidationRequest ? 'present|array' : 'present|array|min:1',
            'records.*' => 'required|array',
            'records.*.value' => [
                'present',
                new FundRequestRecordValueRule($fund, $this),
            ],
            'records.*.files' => [
                'present',
                new FundRequestRecordFilesRule($fund, $this),
            ],
            'records.*.fund_criterion_id' => [
                'present',
                new FundRequestRecordCriterionIdRule($fund, $this),
            ],
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->get('records', []) as $val) {
            $record_type_key = Arr::get($val, 'record_type_key', false);

            if ($record_type_key) {
                $prefix = (ends_with($record_type_key, '_eligible') ? 'eligible_' : '');

                $messages["records.*.value.required"] =
                    trans("validation.fund_request_request_{$prefix}field_incomplete");
            }
        }

        return $messages;
    }
}
