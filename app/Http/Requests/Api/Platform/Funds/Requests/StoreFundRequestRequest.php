<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\FundRequest;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordCriterionIdRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordFilesRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRecordValueRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRequiredGroupRule;
use App\Rules\FundRequests\FundRequestRecords\FundRequestRequiredRecordsRule;
use App\Services\IConnectApiService\IConnectPrefill;
use App\Services\WalletService\Models\WalletDisclosure;
use App\Services\WalletService\WalletDisclosureMapper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        if ($disclosure = $this->getWalletDisclosure()) {
            $this->merge(['records' => $this->recordsWithWalletPrefills($disclosure)]);
        }

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
                $prefix = (Str::endsWith($record_type_key, '_eligible') ? 'eligible_' : '');

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
        if ($fund->fund_config->wallet_disclosure_flow_id) {
            return null;
        }

        if ($this->iConnectPrefill) {
            return $this->iConnectPrefill;
        }

        if (Gate::allows('viewPersonBsnApiRecords', $fund)) {
            $this->iConnectPrefill = IConnectPrefill::getBsnApiPrefills($fund, $this->identity()->bsn, true);
        }

        return $this->iConnectPrefill;
    }

    /**
     * @param bool $lockForUpdate
     * @throws ValidationException
     * @return WalletDisclosure|null
     */
    public function getWalletDisclosure(bool $lockForUpdate = false): ?WalletDisclosure
    {
        if (!$this->fund->fund_config->wallet_disclosure_flow_id) {
            return null;
        }

        Validator::make($this->only('wallet_disclosure_id'), [
            'wallet_disclosure_id' => ['required', 'integer', 'min:1'],
        ])->validate();

        $query = WalletDisclosure::whereKey($this->input('wallet_disclosure_id'));
        $disclosure = ($lockForUpdate ? $query->lockForUpdate() : $query)->first();

        if (!$disclosure || Gate::denies('show', [
            $disclosure, $this->fund, $this->implementation(), $this->client_type(),
        ])) {
            throw ValidationException::withMessages([
                'wallet_disclosure_id' => trans('wallets.disclosure.unavailable'),
            ]);
        }

        return $disclosure;
    }

    /**
     * @param WalletDisclosure $disclosure
     * @throws ValidationException
     * @return array
     */
    public function recordsWithWalletPrefills(WalletDisclosure $disclosure): array
    {
        Validator::make($this->only('records'), [
            'records' => ['sometimes', 'array'],
            'records.*' => ['required', 'array'],
            'records.*.fund_criterion_id' => ['required', 'integer'],
        ])->validate();

        try {
            $values = resolve(WalletDisclosureMapper::class)->validateRecords($this->fund, $disclosure->records);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'wallet_disclosure_id' => trans('wallets.disclosure.invalid'),
            ]);
        }

        $criteria = $this->fund->criteria->where('fill_type', FundCriterion::FILL_TYPE_PREFILL);
        $prefillIds = $criteria->modelKeys();
        $prefills = $criteria
            ->filter(fn (FundCriterion $criterion) => array_key_exists($criterion->record_type_key, $values))
            ->mapWithKeys(fn (FundCriterion $criterion) => [$criterion->id => [
                'fund_criterion_id' => $criterion->id,
                'value' => $values[$criterion->record_type_key],
                'files' => [],
            ]]);
        $records = collect($this->input('records', []))
            ->reject(fn ($record) => in_array(Arr::get($record, 'fund_criterion_id'), $prefillIds) &&
                !$prefills->has(Arr::get($record, 'fund_criterion_id')))
            ->map(fn ($record) => $prefills->get(Arr::get($record, 'fund_criterion_id'), $record))
            ->unique('fund_criterion_id');

        return [
            ...$records->values()->all(),
            ...$prefills->except($records->pluck('fund_criterion_id')->all())->values()->all(),
        ];
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
