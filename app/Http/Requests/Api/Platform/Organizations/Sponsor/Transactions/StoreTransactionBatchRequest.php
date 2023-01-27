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
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        return [
            'transactions' => 'present|array',
            'transactions.*' => 'bail|required|array',
            'transactions.*.uid' => 'nullable|string|max:20',
            'transactions.*.note' => 'nullable|string|max:280',
            'transactions.*.amount' => $this->amountRules($transactions),
            'transactions.*.voucher_id' => $this->voucherIdRules(),
            'transactions.*.direct_payment_iban' => ['required', new IbanRule()],
            'transactions.*.direct_payment_name' => 'required|string|min:3|max:200',
        ];
    }

    /**
     * @param array|null $transactions
     * @return array
     */
    protected function amountRules(?array $transactions = null): array
    {
        $transactions = $transactions ?: $this->input('transactions');

        return [
            'bail',
            'required',
            'numeric',
            'min:.02',
            new VoucherTransactionBatchItemAmountRule(
                $this->organization,
                // load all models for transactions
                $this->inflateReservationsData($transactions),
            ),
        ];
    }

    /**
     * @return array
     */
    protected function voucherIdRules(): array
    {
        $query = $this->getVouchersQuery()->select('id');

        return [
            'required',
            Rule::exists('vouchers', 'id')
                ->where(fn(QBuilder $builder) => $builder->whereIn('id', $query)),
        ];
    }

    /**
     * @param array $transactions
     * @return \Illuminate\Validation\Validator
     */
    public function validateRows(array $transactions = []): \Illuminate\Validation\Validator
    {
        return Validator::make(compact('transactions'), $this->rules($transactions));
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        $keys = Arr::dot([
            'transactions.*.uid',
            'transactions.*.note',
            'transactions.*.amount',
            'transactions.*.voucher_id',
            'transactions.*.direct_payment_iban',
            'transactions.*.direct_payment_name',
        ]);

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
        /** @var Voucher[] $vouchers */
        $vouchers = $this->getVouchersQuery()
            ->whereIn('id', Arr::pluck($transactions, 'voucher_id'))
            ->with('transactions', 'product_vouchers', 'reimbursements_pending', 'top_up_transactions')
            ->get()
            ->keyBy('id');

        return array_map(fn ($transaction) => array_merge([
            'voucher' => $vouchers[$transaction['voucher_id'] ?? null] ?? null,
            'voucher_id' => ($vouchers[$transaction['voucher_id'] ?? null] ?? null)?->id ?? null,
        ], $transaction), $transactions);
    }

    /**
     * @return Builder
     */
    protected function getVouchersQuery(): Builder
    {
        $builder = Voucher::query()
            ->where(fn (Builder $builder) => VoucherQuery::whereNotExpiredAndActive($builder))
            ->whereNull('product_id');

        $builder->whereHas('fund', function(Builder $builder) {
            FundQuery::whereIsInternalConfiguredAndActive($builder->where([
                'type' => Fund::TYPE_BUDGET,
                'organization_id' => $this->organization->id,
            ]));
        });

        return $builder;
    }
}
