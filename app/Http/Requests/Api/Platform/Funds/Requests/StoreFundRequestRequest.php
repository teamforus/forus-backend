<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordCriterionIdRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordFilesRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordValueRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRequiredGroupRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRequiredRecordsRule;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * @property Fund $fund
 */
class StoreFundRequestRequest extends BaseFormRequest
{
    protected ?array $iConnectPrefill = null;
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
     * @return array
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'physical_card_request_address.city' => trans('validation.attributes.city'),
            'physical_card_request_address.street' => trans('validation.attributes.street'),
            'physical_card_request_address.house_nr' => trans('validation.attributes.house_nr'),
            'physical_card_request_address.house_nr_addition' => trans('validation.attributes.house_nr_addition'),
            'physical_card_request_address.postal_code' => trans('validation.attributes.postal_code'),
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $records = $this->input('records');

        return [
            ...$this->recordsRule($this->fund, is_array($records) ? $records : []),
            ...$this->contactInformationRule($this->fund),
            ...$this->physicalCardRequestRule($this->fund),
        ];
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
     * @param array $records
     * @param bool $forPrevalidationRequestsCSV
     * @return array
     */
    public function recordsRule(Fund $fund, array $records, bool $forPrevalidationRequestsCSV = false): array
    {
        $values = Arr::pluck($records, 'value', 'fund_criterion_id');

        if ($this->isValidationRequest && !$this->has('records')) {
            return [];
        }

        return [
            'records' => [
                'present',
                'array',
                'min:1',
                new FundRequestRequiredRecordsRule($fund, $this, $values, $this->isValidationRequest),
            ],
            'criteria_groups' => [
                'present',
                'array',
            ],
            'criteria_groups.*' => [
                'required',
                new FundRequestRequiredGroupRule($fund, $this, $values),
            ],
            'records.*' => 'required|array',
            'records.*.value' => [
                'present',
                new FundRequestRecordValueRule($fund, $this, $values, $records, $this->isValidationRequest, $forPrevalidationRequestsCSV),
            ],
            'records.*.files' => $forPrevalidationRequestsCSV ? [] : [
                'present',
                new FundRequestRecordFilesRule($fund, $this, $values, $records),
            ],
            'records.*.fund_criterion_id' => [
                'present',
                'numeric',
                new FundRequestRecordCriterionIdRule($fund, $this),
            ],
        ];
    }

    /**
     * @param Fund $fund
     * @return array|null
     */
    public function getIConnectPrefills(Fund $fund): ?array
    {
        if ($this->iConnectPrefill) {
            return $this->iConnectPrefill;
        }

        if (Gate::allows('viewPersonBsnApiRecords', $fund)) {
            $this->iConnectPrefill = IConnectPrefill::getBsnApiPrefills($fund, $this->identity()->bsn, true);
        }

        return $this->iConnectPrefill;
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function physicalCardRequestRule(Fund $fund): array
    {
        if (!$fund->fund_config->getApplicationPhysicalCardRequestType()) {
            return [];
        }

        if ($this->isValidationRequest && !$this->has('physical_card_request_address')) {
            return [];
        }

        return [
            'physical_card_request_address' => ['required', 'array'],
            'physical_card_request_address.city' => ['required', 'city_name'],
            'physical_card_request_address.street' => ['required', 'street_name'],
            'physical_card_request_address.house_nr' => ['required', 'house_number'],
            'physical_card_request_address.house_nr_addition' => ['nullable', 'house_addition'],
            'physical_card_request_address.postal_code' => ['required', 'postcode'],
        ];
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
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Inject fund groups so rules can attach per-group validation errors.
            'criteria_groups' => $this->fund->criteria_groups->pluck('id', 'id')->toArray(),
        ]);
    }
}
