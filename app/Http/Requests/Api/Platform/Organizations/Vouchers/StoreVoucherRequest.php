<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Rules\ProductIdInStockRule;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * Class StoreVoucherRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class StoreVoucherRequest extends BaseFormRequest
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
        /** @var Fund $fund */
        $fund = $this->organization->funds()->find($this->input('fund_id'));
        $bsn_enabled = $this->organization->bsn_enabled;

        $funds = $this->organization->funds()->where(function(Builder $builder) {
            FundQuery::whereIsInternal($builder);
            FundQuery::whereIsConfiguredByForus($builder);
        });

        return [
            'fund_id'   => [
                'required',
                Rule::exists('funds', 'id')->whereIn('id', $funds->pluck('id')->toArray())
            ],
            'email'     => 'nullable|required_if:assign_by_type,email|email:strict',
            'bsn'       => $bsn_enabled ? 'nullable|required_if:assign_by_type,bsn|digits:9' : 'nullable|in:',
            'note'      => 'nullable|string|max:280',
            'amount'    => [
                $fund && $fund->isTypeBudget() ? 'required_without:product_id' : 'nullable',
                'numeric',
                'between:.1,' . ($fund ? currency_format($fund->getMaxAmountPerVoucher()) : .1),
            ],
            'expire_at' => [
                'nullable',
                'date_format:Y-m-d',
                'after:' . ($fund ? $fund->start_date->format('Y-m-d') : null),
                'before_or_equal:' . ($fund ? $fund->end_date->format('Y-m-d') : null),
            ],
            'product_id' => [
                $fund && $fund->isTypeBudget() ? 'required_without:amount' : 'nullable',
                'exists:products,id',
                new ProductIdInStockRule($fund),
            ],
            'activate'              => 'boolean',
            'activation_code'       => 'boolean',
            'activation_code_uid'   => 'nullable|string|max:20',
            'assign_by_type'        => 'required|in:' . $this->availableAssignTypes($bsn_enabled),
            'limit_multiplier'      => 'nullable|numeric|min:1|max:1000',
        ];
    }

    /**
     * @param bool $bsn_enabled
     * @return string
     */
    protected function availableAssignTypes(bool $bsn_enabled): string
    {
        return implode(",", array_filter([
            'email', 'activation_code_uid', $bsn_enabled ? 'bsn' : null,
        ]));
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'assign_by_type' => 'methode',
        ];
    }

    /**
     * @return array
     */
    public function messages(): array {
        return [
            'amount.between' => 'Er staat niet genoeg budget op het fonds. '.
                'Het maximale tegoed van een voucher is €:max. '.
                'Vul het fonds aan om deze voucher aan te maken.'
        ];
    }
}
