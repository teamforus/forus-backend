<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Models\Fund;
use App\Rules\Base\IbanNameRule;
use App\Rules\Base\IbanRule;
use App\Rules\BsnRule;
use App\Rules\Vouchers\VoucherBatchRules;
use App\Rules\Vouchers\VoucherRecipientRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreBatchVoucherRequest extends BaseStoreVouchersRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fund = $this->getFund();
        $bsn_enabled = $this->organization->bsn_enabled;
        $vouchers = $this->input('vouchers');
        $voucher_counts = collect($vouchers)->countBy('product_id')->all();

        return [
            'fund_id' => $this->fundIdRule(),
            'vouchers' => 'required|array|max:1000',
            'vouchers.*' => [
                'required',
                'array',
                Rule::forEach(function (mixed $voucher) use ($bsn_enabled): array {
                    if (!$bsn_enabled || !is_array($voucher) || !empty($voucher['email'])) {
                        return [];
                    }

                    return ['bsn' => [new VoucherRecipientRule(byBsn: true)]];
                }),
            ],
            'vouchers.*.amount' => VoucherBatchRules::ruleAmount($fund, $vouchers),
            'vouchers.*.product_id' => VoucherBatchRules::ruleProductId($fund, $voucher_counts),
            'vouchers.*.expire_at' => $this->expireAtRule($fund),
            'vouchers.*.note' => 'nullable|string|max:280',
            'vouchers.*.email' => [
                'nullable',
                ...$this->emailRules(),
                new VoucherRecipientRule(),
            ],
            'vouchers.*.bsn' => $bsn_enabled ? [
                'nullable',
                new BsnRule(),
            ] : 'nullable|in:',
            'vouchers.*.activate' => 'boolean',
            'vouchers.*.activation_code' => 'boolean',
            'vouchers.*.client_uid' => 'nullable|string|max:20',
            'vouchers.*.limit_multiplier' => VoucherBatchRules::ruleLimitMultiplier(),
            'vouchers.*.notify_provider' => 'nullable|boolean',
            'vouchers.*.records' => $this->recordsRule(),
            ...$this->uploadedCSVFileRules(),
            ...$this->directPaymentRules($fund),
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'vouchers.*.direct_payment_iban.in' => '[direct_payment_iban] - direct payments are not available for the target fund.',
            'vouchers.*.direct_payment_name.in' => '[direct_payment_name] - direct payments are not available for the target fund.',
        ]);
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        $keys = Arr::dot(array_keys($this->rules()));

        return array_combine($keys, array_map(static function ($key) {
            $value = last(explode('.', $key));

            return trans_fb('validation.attributes.' . $value, $value);
        }, $keys));
    }

    /**
     * @param Fund $fund
     * @return array|string[]
     */
    protected function directPaymentRules(Fund $fund): array
    {
        if ($fund->generatorDirectPaymentsAllowed()) {
            return [
                'vouchers.*.direct_payment_iban' => [
                    'nullable',
                    'prohibits:vouchers.*.product_id',
                    new IbanRule(),
                ],
                'vouchers.*.direct_payment_name' => [
                    'required_with:vouchers.*.direct_payment_iban',
                    new IbanNameRule(),
                ],
            ];
        }

        return [
            'vouchers.*.direct_payment_iban' => 'nullable|in:',
            'vouchers.*.direct_payment_name' => 'required_with:vouchers.*.direct_payment_iban|in:',
        ];
    }

    /**
     * @return array
     */
    private function fundIdRule(): array
    {
        $fundIds = $this->organization->funds()->pluck('id')->toArray();

        return [
            'required',
            Rule::exists('funds', 'id')->whereIn('id', $fundIds),
        ];
    }

    /**
     * @param Fund $fund
     * @return string[]
     */
    private function expireAtRule(Fund $fund): array
    {
        return [
            'nullable',
            'date_format:Y-m-d',
            'after:' . $fund->start_date->format('Y-m-d'),
            'before_or_equal:' . $fund->end_date->format('Y-m-d'),
        ];
    }

    /**
     * @return Fund
     */
    private function getFund(): Fund
    {
        /** @var Fund $fund */
        $fund = $this->organization->funds()->findOrFail($this->input('fund_id'));

        return $fund;
    }
}
