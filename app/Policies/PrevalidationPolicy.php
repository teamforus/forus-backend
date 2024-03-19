<?php

namespace App\Policies;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Identity;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;

class PrevalidationPolicy extends BasePolicy
{


    /**
     * @return string
     *
     * @psalm-return 'prevalidations'
     */
    public function getPolicyKey(): string
    {
        return "prevalidations";
    }
}
