<?php

namespace App\Http\Requests\Api\Platform\Payouts;

use App\Http\Requests\BaseFormRequest;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Rules\Payouts\VoucherPayoutAmountRule;
use App\Rules\Payouts\VoucherPayoutCountRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StorePayoutRequest extends BaseFormRequest
{
    protected ?Voucher $voucher = null;

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return
            $this->voucher()
            && Gate::allows('storePayoutRequester', [VoucherTransaction::class, $this->voucher()]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $voucher = $this->voucher();

        return [
            'voucher_id' => [
                'required',
                'integer',
                Rule::exists('vouchers', 'id')->where('identity_id', $this->auth_id()),
            ],
            'amount' => [
                'required',
                'numeric',
                new VoucherPayoutCountRule($voucher),
                new VoucherPayoutAmountRule($voucher),
            ],
        ];
    }

    /**
     * @return Voucher|null
     */
    public function voucher(): ?Voucher
    {
        if ($this->voucher instanceof Voucher) {
            return $this->voucher;
        }

        return $this->voucher = Voucher::find($this->input('voucher_id'));
    }
}
