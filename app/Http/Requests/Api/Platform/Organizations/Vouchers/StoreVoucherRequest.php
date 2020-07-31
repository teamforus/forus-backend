<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Models\Fund;
use App\Models\Product;
use App\Rules\ValidPrevalidationCodeRule;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;

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
        $fund = Fund::whereHas('organization', static function(Builder $builder) {
            OrganizationQuery::whereHasPermissions($builder, auth_address(), [
                'manage_vouchers'
            ]);
        })->findOrFail($this->input('fund_id'));

        $fundsId = Fund::whereHas('organization', static function(Builder $builder) {
            OrganizationQuery::whereHasPermissions($builder, auth_address(), [
                'manage_vouchers'
            ]);
        })->pluck('funds.id')->toArray();

        $startDate = $fund->start_date->format('Y-m-d');
        $endDate = $fund->end_date->format('Y-m-d');
        $max_allowed = config('forus.funds.max_sponsor_voucher_amount');
        $max = min($fund ? $fund->budget_left : $max_allowed, $max_allowed);

        $productsQuery = ProductQuery::approvedForFundsAndActiveFilter(Product::query(), $fund->id);
        $validProducts = $productsQuery->pluck('id')->toArray();

        return [
            'fund_id'   => [
                'required',
                'exists:funds,id',
                Rule::in($fundsId)
            ],
            'email'     => 'nullable|email:strict,dns',
            'note'      => 'nullable|string|max:280',
            'amount'    => [
                'required_without_all:product_id', 'numeric',
                'between:.1,' . $max
            ],
            'expire_at' => [
                'nullable', 'date_format:Y-m-d', 'after:' . $startDate, 'before_or_equal:' . $endDate,
            ],
            'activation_code' => [
                'nullable', new ValidPrevalidationCodeRule($fund),
            ],
            'product_id' => [
                'required_without_all:amount',
                Rule::exists('products', 'id')->where(static function(
                    \Illuminate\Database\Query\Builder $builder
                ) use ($validProducts) {
                    return $builder->whereIn('id', $validProducts);
                }),
            ],
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
