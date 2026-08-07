<?php

namespace App\Http\Requests\Api\Platform\Organizations\FundProductLimits;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundProductLimit;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductQuery;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class StoreFundProductLimitsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [FundProductLimit::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'fund_id' => [
                'required',
                Rule::in($this->getAvailableFunds()->pluck('id')->toArray()),
            ],
            'state' => [
                'required',
                Rule::in([FundProductLimit::STATE_ACTIVE, FundProductLimit::STATE_INACTIVE]),
            ],
            'type' => [
                'required',
                Rule::in([FundProductLimit::SCOPE_ALL_EXCEPT_SELECTED, FundProductLimit::SCOPE_ONLY_SELECTED]),
            ],
            'limit' => [
                'required',
                'integer',
                'min:1',
            ],
            'products' => [
                'required_if:type,' . FundProductLimit::SCOPE_ONLY_SELECTED,
                'array',
            ],
            'products.*' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (!$this->productIsApprovedForFund($value, (int) $this->input('fund_id'))) {
                        $fail(__('validation.product_voucher.product_not_found'));
                    }
                },
            ],
        ];
    }

    /**
     * @return Builder|Relation|Arrayable
     */
    private function getAvailableFunds(): Builder|Relation|Arrayable
    {
        return FundQuery::whereIsInternalConfiguredAndNotClosed($this->organization->funds());
    }

    /**
     * @param mixed $productId
     * @param int $fundId
     * @return bool
     */
    private function productIsApprovedForFund(mixed $productId, int $fundId): bool
    {
        return $fundId > 0 && ProductQuery::approvedForFundsFilter(
            Product::where('id', $productId),
            $fundId,
        )->exists();
    }
}
