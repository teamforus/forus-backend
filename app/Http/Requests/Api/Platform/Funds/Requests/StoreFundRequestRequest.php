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
     *
     * @return string[][]
     *
     * @psalm-return array{contact_information: list{0?: 'nullable'|'required', 1?: 'string', 2?: 'min:5', 3?: 'max:2000'}}
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
     *
     * @return ((FundRequestRecordCriterionIdRule|FundRequestRecordFilesRule|FundRequestRecordValueRule|string)[]|string)[]
     *
     * @psalm-return array{records: 'present|array'|'present|array|min:1', 'records.*': 'required|array', 'records.*.value': list{'present', FundRequestRecordValueRule}, 'records.*.files': list{'present', FundRequestRecordFilesRule}, 'records.*.fund_criterion_id': list{'present', FundRequestRecordCriterionIdRule}}
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
     * @return (\Illuminate\Contracts\Translation\Translator|array|null|string)[]
     *
     * @psalm-return array{'records.*.value.required'?: \Illuminate\Contracts\Translation\Translator|array|null|string}
     */
    public function messages(): array
    {
        $messages = [];
        $records = $this->get('records', []);

        foreach (is_array($records) ? $records : [] as $val) {
            $record_type_key = Arr::get($val, 'record_type_key', false);

            if ($record_type_key) {
                $prefix = (ends_with($record_type_key, '_eligible') ? 'eligible_' : '');

                $messages["records.*.value.required"] = trans(
                    "validation.fund_request_request_{$prefix}field_incomplete",
                );
            }
        }

        return $messages;
    }
}
