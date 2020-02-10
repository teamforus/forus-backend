<?php

namespace App\Policies;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Prevalidation;
use App\Models\Voucher;

class PrevalidationPolicy extends BasePolicy
{
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
    public function viewAny(
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
     * @return bool|void
     * @throws AuthorizationJsonException
     */
    public function redeem(
        $identity_address,
        Prevalidation $prevalidation = null
    ) {
        if (empty($identity_address)) {
            return false;
        }

        if (!$prevalidation || !$prevalidation->exists()) {
            $this->deny('wrong_code');
        }

        if ($prevalidation->state != 'pending') {
            $this->deny('used');
        }

        if (Voucher::where([
                'identity_address' => $identity_address,
                'fund_id' => $prevalidation->fund_id,
            ])->count() > 0) {
            $this->deny('used_same_fund');
        }

        return true;
    }

    /**
     * @return string
     */
    public function getPolicyKey(): string
    {
        return "prevalidations";
    }
}
