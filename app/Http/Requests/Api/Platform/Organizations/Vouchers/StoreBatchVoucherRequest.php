<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Rules\Base\IbanRule;
use App\Rules\ProductIdInStockRule;
use App\Rules\VouchersUploadArrayRule;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class StoreBatchVoucherRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identityCan($this->identity(), 'manage_vouchers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fund = $this->getFund();
        $bsn_enabled = $this->organization->bsn_enabled;

        return array_merge([
            'fund_id'                           => $this->fundIdRule(),
            'vouchers'                          => ['required', new VouchersUploadArrayRule($fund)],
            'vouchers.*'                        => 'required|array',
            'vouchers.*.amount'                 => $this->amountRule($fund),
            'vouchers.*.product_id'             => $this->productIdRule($fund),
            'vouchers.*.expire_at'              => $this->expireAtRule($fund),
            'vouchers.*.note'                   => 'nullable|string|max:280',
            'vouchers.*.email'                  => 'nullable|string|email:strict',
            'vouchers.*.bsn'                    => $bsn_enabled ? 'nullable|string|digits:9' : 'nullable|in:',
            'vouchers.*.activate'               => 'boolean',
            'vouchers.*.activation_code'        => 'boolean',
            'vouchers.*.client_uid'             => 'nullable|string|max:20',
            'vouchers.*.limit_multiplier'       => 'nullable|numeric|min:1|max:1000',
        ], $this->directPaymentRules($fund));
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
                    'string',
                    'min:3',
                    'max:280',
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
    private function fundIdRule(): array {
        $fundIds = $this->organization->funds()->pluck('id')->toArray();

        return [
            'required',
            Rule::exists('funds', 'id')->whereIn('id', $fundIds)
        ];
    }

    /**
     * @param Fund $fund
     * @return string[]
     */
    private function expireAtRule(Fund $fund): array {
        return [
            'nullable',
            'date_format:Y-m-d',
            'after:' . $fund->start_date->format('Y-m-d'),
            'before_or_equal:' . $fund->end_date->format('Y-m-d'),
        ];
    }

    /**
     * @param Fund $fund
     * @return string|string[]
     */
    private function amountRule(Fund $fund): array|string
    {
        return $fund->isTypeBudget() ? [
            'required_without:vouchers.*.product_id',
            'numeric',
            'between:.1,' . currency_format($fund->getMaxAmountPerVoucher()),
        ] : 'nullable';
    }

    /**
     * @param Fund $fund
     * @return string[]
     */
    private function productIdRule(Fund $fund): array
    {
        $vouchers = $this->input('vouchers');

        $rule = $fund->isTypeBudget() ? [
            'required_without:vouchers.*.amount',
        ] : [];

        return array_merge($rule, [
            'exists:products,id',
            new ProductIdInStockRule($fund, collect($vouchers)->countBy('product_id')->toArray())
        ]);
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
        $keys = array_dot(array_keys($this->rules()));

        return array_combine($keys, array_map(static function($key) {
            $value = last(explode('.', $key));
            return trans_fb("validation.attributes." . $value, $value);
        }, $keys));
    }
}
