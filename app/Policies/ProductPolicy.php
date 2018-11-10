<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Product;
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
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        $identity_address,
        Organization $organization = null
    ) {
        return $this->store($identity_address, $organization);
    }

    /**
     * @return bool
     */
    public function indexPublic() {
        return true;
    }

    /**
     * @param $identity_address
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        $identity_address,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);
        }

        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        $identity_address,
        Product $product,
        Organization $organization = null
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
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        $identity_address,
        Product $product,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);

            if ($product->organization_id != $organization->id) {
                return false;
            }
        }

        return strcmp(
                $product->organization->identity_address, $identity_address) == 0;
    }

    /**
     * To be able to make an reservation product should not
     * be expired or sold out
     *
     * @param $identity_address
     * @param Product $product
     * @return bool
     */
    public function reserve(
        $identity_address,
        Product $product
    ) {
        return !empty($identity_address) && !$product->expired && !$product->sold_out;
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        $identity_address,
        Product $product,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $product, $organization);
    }
}
