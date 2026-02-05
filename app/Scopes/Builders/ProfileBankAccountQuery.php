<?php

namespace App\Scopes\Builders;

use App\Models\Organization;
use App\Models\ProfileBankAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProfileBankAccountQuery
{
    /**
     * @param Builder|Relation|ProfileBankAccount $builder
     * @param Organization $organization
     * @return Builder|Relation|ProfileBankAccount
     */
    public static function whereHasPayoutBankAccountForOrganization(
        Builder|Relation|ProfileBankAccount $builder,
        Organization $organization,
    ): Builder|Relation|ProfileBankAccount {
        return $builder
            ->whereRelation('profile', 'organization_id', $organization->id)
            ->whereNotNull('iban')
            ->where('iban', '!=', '')
            ->whereNotNull('name')
            ->where('name', '!=', '');
    }
}
