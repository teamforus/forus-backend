<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
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
     * @return mixed
     */
    public function index($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function show($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return bool
     */
    public function store($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @return bool
     */
    public function update($identity_address, Product $product) {
        return strcmp(
            $product->organization->identity_address,
            $identity_address
            ) == 0;
    }

    /**
     * To be able to make an reservation product should not
     * be expired or sold out
     *
     * @param $identity_address
     * @param Product $product
     * @return bool
     */
    public function reserve($identity_address, Product $product) {
        return !$product->expired && !$product->sold_out;
    }

    /**
     *  Delete product policy
     *
     * @param $identity_address
     * @param Product $product
     * @return bool
     */
    public function destroy($identity_address, Product $product) {
        // Provider should be able to delete only expired or sold out products
        if (!$product->expired && !$product->sold_out) {
            return false;
        }

        // Only product organization owner should be able to delete products
        return strcmp(
            $product->organization->identity_address,
            $identity_address
        ) == 0;
    }
}
