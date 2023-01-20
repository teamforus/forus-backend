<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\Base\IbanRule;
use App\Rules\Transaction\VoucherTransactionBatchItemAmountRule;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

/**
 * @property Organization $organization
 */
class StoreTransactionBatchRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @param array|null $transactions
     * @return array
     */
    public function rules(?array $transactions = null): array
    {
        $transactions = $transactions ?: $this->input('transactions');

        // load all models for transactions collection
        $data = $this->inflateReservationsData($transactions);

        return [
            'transactions.*' => 'bail|required|array',
            'transactions.*.amount' => [
                'bail',
                'required',
                'numeric',
                'min:.02',
                new VoucherTransactionBatchItemAmountRule($this->organization, $data),
            ],
            'transactions.*.note' => 'nullable|string|max:280',
            'transactions.*.uid' => 'nullable|string|max:20',
            'transactions.*.direct_payment_iban' => [
                'required', new IbanRule(),
            ],
            'transactions.*.direct_payment_name' => [
                'required', 'string', 'min:3', 'max:200',
            ],
        ];
    }

    /**
     * @param array $transactions
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
     */
    public function validateRows(array $transactions = []): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make(compact('transactions'), $this->rules($transactions));
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        $keys = [
            'transactions.*.amount',
            'transactions.*.note',
            'transactions.*.voucher_id',
            'transactions.*.uid',
            'transactions.*.direct_payment_iban',
            'transactions.*.direct_payment_name',
        ];

        $keys = array_dot($keys);

        return array_combine($keys, array_map(static function($key) {
            $value = last(explode('.', $key));
            return trans_fb("validation.attributes." . $value, $value);
        }, $keys));
    }

    /**
     * @param array $transactions
     * @return array
     */
    public function inflateReservationsData(array $transactions = []): array
    {
        $builder = VoucherQuery::whereNotExpiredAndActive(Voucher::query())->with([
            'transactions', 'product_vouchers', 'reimbursements_pending', 'top_up_transactions',
        ]);

        $builder->whereHas('fund', function(Builder $builder) {
            $builder->where('funds.type', Fund::TYPE_BUDGET);

            FundQuery::whereIsInternalConfiguredAndActive($builder->where([
                'organization_id' => $this->organization->id,
            ]))->select('funds.id');
        })->whereIn('id', collect($transactions)->pluck('voucher_id')->all());

        $vouchers = $builder->whereNull('product_id')->get()->keyBy('id');

        return collect($transactions)->map(function ($transaction) use ($vouchers) {
            /** @var Voucher|null $voucher */
            $voucher = $vouchers[$transaction['voucher_id'] ?? null] ?? null;
            $voucher_id = $voucher?->id;

            return array_merge(compact('voucher', 'voucher_id'), $transaction);
        })->toArray();
    }
}
