<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordCriterionIdRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordFilesRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordValueRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRequiredRecordsRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

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
        return
            Gate::allows('check', $this->fund) &&
            Gate::allows('createAsRequester', [FundRequest::class, $this->fund]) &&
            $this->fund->state === Fund::STATE_ACTIVE &&
            !$this->fund->getResolvingError();
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
     * @return array
     */
    public function messages(): array
    {
        $messages = [];
        $records = $this->get('records', []);

        foreach (is_array($records) ? $records : [] as $val) {
            $record_type_key = Arr::get($val, 'record_type_key', false);

            if ($record_type_key) {
                $prefix = (ends_with($record_type_key, '_eligible') ? 'eligible_' : '');

                $messages['records.*.value.required'] = trans(
                    "validation.fund_request_request_{$prefix}field_incomplete",
                );
            }
        }

        return $messages;
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
            ] : ['nullable', 'string'],
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function recordsRule(Fund $fund): array
    {
        $values = $this->input('records');
        $values = is_array($values) ? Arr::pluck($values, 'value', 'fund_criterion_id') : [];

        return [
            'records' => [
                'present',
                'array',
                'min:1',
                new FundRequestRequiredRecordsRule($fund, $this, $values, $this->isValidationRequest),
            ],
            'records.*' => 'required|array',
            'records.*.value' => [
                'present',
                new FundRequestRecordValueRule($fund, $this, $values),
            ],
            'records.*.files' => [
                'present',
                new FundRequestRecordFilesRule($fund, $this),
            ],
            'records.*.fund_criterion_id' => [
                'present',
                'numeric',
                new FundRequestRecordCriterionIdRule($fund, $this),
            ],
        ];
    }
}
