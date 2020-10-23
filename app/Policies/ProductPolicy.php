<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan(
            $identity_address,
            'manage_products'
        );
    }

    /**
     * @return bool
     */
    public function viewAnyPublic() {
        return true;
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function store(
        $identity_address,
        Organization $organization
    ): bool {
        $hard_limit = config('forus.features.dashboard.organizations.products.hard_limit');

        return $organization->identityCan($identity_address, 'manage_products') &&
            $organization->products->count() < $hard_limit;
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return bool
     */
    public function show(
        $identity_address,
        Product $product,
        Organization $organization
    ) {
        return $this->update($identity_address, $product, $organization);
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return bool
     */
    public function showFunds(
        $identity_address,
        Product $product,
        Organization $organization
    ) {
        return $this->update($identity_address, $product, $organization);
    }

    /**
     * @return bool
     */
    public function showPublic() {
        return true;
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return bool
     */
    public function update(
        $identity_address,
        Product $product,
        Organization $organization
    ) {
        if ($product->organization_id != $organization->id) {
            return false;
        }

        return $product->organization->identityCan(
            $identity_address,
            'manage_products'
        );
    }

    /**
     * To be able to make an reservation product should not
     * be expired or sold out and voucher not expired
     *
     * @param $identity_address
     * @param Voucher $voucher
     * @param Product $product
     * @return bool
     */
    public function reserve(
        $identity_address,
        Product $product,
        Voucher $voucher
    ) {
        if (empty($identity_address)) {
            return false;
        }

        // check validity
        return ProductQuery::approvedForFundsAndActiveFilter(
            Product::query(),
            $voucher->fund_id
        )->where('id', '=', $product->id)->exists();
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return bool
     */
    public function destroy(
        $identity_address,
        Product $product,
        Organization $organization
    ) {
        return $this->update($identity_address, $product, $organization);
    }
}
