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
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function store(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Prevalidation $prevalidation
     * @return bool
     * @throws AuthorizationJsonException
     * @noinspection PhpUnused
     */
    public function redeem(Identity $identity, Prevalidation $prevalidation): bool
    {
        if (!$identity->exists) {
            return false;
        }

        if (!$prevalidation->exists()) {
            $this->deny('wrong_code');
        }

        if ($prevalidation->state !== $prevalidation::STATE_PENDING) {
            $this->deny('used');
        }

        if (VoucherQuery::whereNotExpired(Voucher::where([
            'fund_id' => $prevalidation->fund_id,
            'identity_address' => $identity->address,
        ]))->exists()) {
            $this->deny('used_same_fund');
        }

        return true;
    }

    /**
     * @param Identity $identity
     * @param Prevalidation $prevalidation
     * @return bool
     * @noinspection PhpUnused
     */
    public function destroy(Identity $identity, Prevalidation $prevalidation): bool
    {
        $organization = $prevalidation->organization;
        $isCreator = $prevalidation->identity_address === $identity->address;
        $isValidator = $organization?->identityCan($identity, 'validate_records');

        return ($isCreator || $isValidator) && !$prevalidation->is_used;
    }

    /**
     * @return string
     */
    public function getPolicyKey(): string
    {
        return "prevalidations";
    }
}
