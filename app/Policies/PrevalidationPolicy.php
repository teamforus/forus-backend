<?php

namespace App\Policies;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;

/**
 * Class PrevalidationPolicy
 * @package App\Policies
 */
class PrevalidationPolicy extends BasePolicy
{
    /**
     * @param string|null $identity_address
     * @return bool
     */
    public function viewAny(?string $identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param string|null $identity_address
     * @return bool
     */
    public function show(?string $identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param string|null $identity_address
     * @return bool
     */
    public function store(?string $identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param string|null $identity_address
     * @param Prevalidation $prevalidation
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function redeem(?string $identity_address, Prevalidation $prevalidation): bool
    {
        if (empty($identity_address)) {
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
            'identity_address' => $identity_address,
        ]))->exists()) {
            $this->deny('used_same_fund');
        }

        return true;
    }

    /**
     * @param string|null $identity_address
     * @param Prevalidation $prevalidation
     * @return bool
     */
    public function destroy(?string $identity_address, Prevalidation $prevalidation): bool
    {
        $organization = $prevalidation->organization;
        $isCreator = $prevalidation->identity_address === $identity_address;
        $isOrganizationEmployee = false;

        if ($organization) {
            $isOrganizationEmployee = $organization->employeesWithPermissionsQuery([
                'validate_records'
            ])->where(compact('identity_address'))->exists();
        }

        return !$prevalidation->is_used && ($isCreator || $isOrganizationEmployee);
    }

    /**
     * @return string
     */
    public function getPolicyKey(): string
    {
        return "prevalidations";
    }
}
