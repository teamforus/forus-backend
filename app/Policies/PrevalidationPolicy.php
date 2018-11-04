<?php

namespace App\Policies;

use App\Models\Prevalidation;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrevalidationPolicy
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
    public function index(
        $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return bool
     */
    public function show(
        $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return bool
     */
    public function store(
        $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Prevalidation $prevalidation
     * @return bool
     */
    public function redeem(
        $identity_address,
        Prevalidation $prevalidation
    ) {
        return !empty($identity_address) && ($prevalidation->state == 'pending');
    }
}
