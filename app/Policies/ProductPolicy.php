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
     * @param $identity_address
     * @param Product $product
     * @return bool
     */
    public function destroy($identity_address, Product $product) {
        return strcmp(
            $product->organization->identity_address,
            $identity_address
        ) == 0;
    }
}
