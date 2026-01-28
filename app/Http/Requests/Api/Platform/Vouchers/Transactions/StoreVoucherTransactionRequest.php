<?php

namespace App\Http\Requests\Api\Platform\Vouchers\Transactions;

use App\Exceptions\AuthorizationJsonException;
use App\Helpers\Locker;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\UnauthorizedException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @property VoucherToken $voucher_address_or_physical_code
 */
class StoreVoucherTransactionRequest extends BaseFormRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->maxAttempts = 1;
        $this->decayMinutes = (int) (Config::get('forus.transactions.hard_limit') / 60);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException|AuthorizationJsonException
     * @return bool
     */
    public function authorize(): bool
    {
        $this->throttleWithKey('to_many_attempts', $this, 'make_transaction', null, 403);

        $voucher = $this->voucher_address_or_physical_code->voucher;
        $locker = new Locker("store_transactions.$voucher->id");

        if (!$locker->waitForUnlockAndLock()) {
            abort(429, 'To many requests, please try again later.');
        }

        if ($this->has('product_id') && $this->has('amount')) {
            abort(422, 'Je kan alleen `product_id` of `amount` indienen maar niet beide tegelijkertijd.');
        }

        $authorized = $this->has('product_id') ?
            Gate::allows('useAsProviderWithProducts', [$voucher, $this->input('product_id')]) :
            Gate::allows('useAsProvider', $voucher);

        Gate::authorize('makeTransactionThrottle', $voucher);

        return $authorized;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $voucher = $this->voucher_address_or_physical_code->voucher;
        $commonRules = $this->commonRules();

        if ($voucher->isBudgetType()) {
            return [...$this->budgetVoucherRules($voucher), ...$commonRules];
        }

        if ($voucher->isProductType()) {
            return $commonRules;
        }

        throw new UnauthorizedException('Invalid voucher type.');
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.max' => trans('validation.voucher.not_enough_funds'),
        ];
    }

    /**
     * @return string[]
     */
    private function commonRules(): array
    {
        return [
            'note' => 'nullable|string|between:2,255',
        ];
    }

    /**
     * @param Voucher $voucher
     * @return \string[][]
     */
    private function budgetVoucherRules(Voucher $voucher): array
    {
        return [
            'product_id' => [
                $this->has('amount') ? 'nullable' : 'required',
                'in:' . implode(',', $this->getValidProducts($voucher)),
            ],
            'amount' => [
                $this->has('product_id') ? 'nullable' : 'required',
                'numeric',
                'min:.02',
                'max:' . number_format($voucher->amount_available, 2, '.', ''),
            ],
            'amount_extra_cash' => [
                'nullable',
                'numeric',
                'min:.02',
                'max:' . number_format(
                    config('forus.transactions.max_amount_extra_cash'),
                    2,
                    '.',
                    ''
                ),
            ],
            'organization_id' => [
                $this->has('amount') ? 'required' : 'nullable',
                'exists:organizations,id',
                'in:' . $this->getValidOrganizations($voucher)->pluck('id')->implode(','),
            ],
        ];
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    private function getValidProducts(Voucher $voucher): array
    {
        return $this->has('organization_id') ? ProductQuery::whereAvailableForVoucher(
            Product::query(),
            $voucher,
            Organization::whereId($this->input('organization_id'))->select('id'),
            false
        )->pluck('products.id')->toArray() : [];
    }

    /**
     * @param Voucher $voucher
     * @return Builder
     */
    private function getValidOrganizations(Voucher $voucher): Builder
    {
        if (!$this->has('product_id') && !$this->has('amount')) {
            return Organization::where('id', '<', -999);
        }

        return Organization::query()->where(function (Builder $builder) use ($voucher) {
            $builder->where(function (Builder $builder) use ($voucher) {
                if ($this->has('product_id')) {
                    $builder->whereHas('products', function (Builder $builder) use ($voucher) {
                        ProductQuery::whereAvailableForVoucher($builder, $voucher, null, false);
                    });
                }

                if ($this->has('amount')) {
                    OrganizationQuery::whereHasPermissionToScanVoucher(
                        $builder,
                        $this->auth_address(),
                        $voucher
                    );
                }
            });
        });
    }
}
