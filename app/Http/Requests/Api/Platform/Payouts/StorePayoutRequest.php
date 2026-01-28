<?php

namespace App\Http\Requests\Api\Platform\Payouts;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequest;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Rules\Payouts\VoucherPayoutAmountRule;
use App\Rules\Payouts\VoucherPayoutCountRule;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
        $fundRequestsQuery = $this->eligibleFundRequestsQuery($voucher);

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
            'fund_request_id' => [
                'required',
                'integer',
                Rule::exists('fund_requests', 'id')->where(function (QueryBuilder $q) use ($fundRequestsQuery) {
                    return $q->whereIn('fund_requests.id', (clone $fundRequestsQuery)->select('fund_requests.id'));
                }),
                function (string $attribute, mixed $value, $fail) use ($voucher, $fundRequestsQuery) {
                    if (!$value) {
                        return;
                    }

                    $fundRequest = $fundRequestsQuery->with('records')->find($value);

                    if (!$fundRequest?->getIban(false) || !$fundRequest?->getIbanName(false)) {
                        $fail(trans('validation.exists', ['attribute' => $attribute]));
                    }
                },
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

    /**
     * @return FundRequest|null
     */
    public function fundRequest(): ?FundRequest
    {
        $fundRequestId = $this->input('fund_request_id');
        $voucher = $this->voucher();

        if (!$voucher || !$fundRequestId) {
            return null;
        }

        return $this->eligibleFundRequestsQuery($voucher)->find($fundRequestId);
    }

    /**
     * @param Voucher|null $voucher
     * @return Builder|FundRequest
     */
    protected function eligibleFundRequestsQuery(?Voucher $voucher): Builder|FundRequest
    {
        return FundRequest::query()
            ->where('identity_id', $this->auth_id())
            ->where('state', FundRequest::STATE_APPROVED)
            ->whereRelation('fund', 'organization_id', $voucher->fund->organization_id)
            ->whereHas('vouchers', function (Builder $builder) {
                $builder->where('identity_id', $this->auth_id());
                VoucherQuery::whereNotExpiredAndActive($builder);
            });
    }
}
