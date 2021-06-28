<?php

namespace App\Rules;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Gate;

/**
 * Class ProviderProductReservationBatchItemRule
 * @package App\Rules
 */
class ProviderProductReservationBatchItemRule implements Rule
{
    protected $request;
    protected $organization;
    protected $validationError = 'Invalid reservation item.';

    /**
     * ProviderProductReservationBatchItemRule constructor.
     * @param Organization $organization
     * @param BaseFormRequest|null $request
     */
    public function __construct(Organization $organization, BaseFormRequest $request = null)
    {
        $this->request = $request ?: BaseFormRequest::createFromBase(request());
        $this->organization = $organization;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $number = array_get($value, 'number');
        $product_id = array_get($value, 'product_id');

        $product = Product::find($product_id);
        $voucher = Voucher::findByAddressOrPhysicalCard($number);

        $sponsorIsValid = OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::query(),
            $this->request->auth_address(),
            $voucher
        )->where('organizations.id', $this->organization->id)->exists();

        $addressIsValid = Gate::allows('useAsProvider', $voucher);

        if (!$sponsorIsValid || !$addressIsValid) {
            $this->validationError = [
                'number' => trans('invalid_provider'),
            ];

            return false;
        }

        if (!$product) {
            $this->validationError = [
                'product_id' => trans('validation.exists'),
            ];

            return false;
        }

        $productRule = (new ProductIdToReservationRule($number));

        if (!$productRule->passes('product_id', $product_id)) {
            $this->validationError = [
                'product_id' => $productRule->message(),
            ];

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        return $this->validationError ?: [];
    }
}
