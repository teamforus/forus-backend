<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Models\Fund;
use App\Rules\ProductIdInStockRule;
use App\Rules\ValidPrevalidationCodeRule;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoucherRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        $validFunds = $this->validFundIds(auth_address());
        $fund = Fund::query()->whereIn('id', $validFunds)->findOrFail($this->input('fund_id'));

        $max_allowed = config('forus.funds.max_sponsor_voucher_amount');
        $max = min($fund ? $fund->budget_left : $max_allowed, $max_allowed);

        return [
            'fund_id'   => [
                'required',
                Rule::exists('funds', 'id')->whereIn('id', $validFunds)
            ],
            'email'     => 'nullable|email:strict,dns',
            'note'      => 'nullable|string|max:280',
            'amount'    => [
                'required_without_all:product_id', 'numeric',
                'between:.1,' . $max
            ],
            'expire_at' => [
                'nullable',
                'date_format:Y-m-d',
                'after:' . $fund->start_date->format('Y-m-d'),
                'before_or_equal:' . $fund->end_date->format('Y-m-d'),
            ],
            'activation_code' => [
                'nullable', new ValidPrevalidationCodeRule($fund),
            ],
            'product_id' => [
                'required_without:vouchers.*.amount',
                'exists:products,id',
                new ProductIdInStockRule($fund)
            ],
        ];
    }

    /**
     * @param $identity_address
     * @return array
     */
    private function validFundIds($identity_address): array {
        return Fund::whereHas('organization', static function($builder) use ($identity_address) {
            OrganizationQuery::whereHasPermissions($builder, $identity_address, 'manage_vouchers');
        })->pluck('funds.id')->toArray();
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
