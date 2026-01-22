<?php

namespace App\Searches\Sponsor\PayoutBankAccounts;

use App\Models\Organization;
use App\Models\ProfileBankAccount;
use App\Scopes\Builders\ProfileBankAccountQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProfilePayoutBankAccountSearch extends BasePayoutBankAccountSearch
{
    /**
     * @return Builder|Relation
     */
    public function query(): Builder|Relation
    {
        /** @var Builder|Relation|ProfileBankAccount $builder */
        $builder = parent::query();

        $q = $this->getFilter('q');
        $bsnEnabled = $this->getFilter('bsn_enabled');
        $identityId = $this->getFilter('identity_id');

        if ($identityId) {
            $builder->whereHas('profile', fn (Builder $builder) => $builder->where('identity_id', $identityId));
        }

        if ($q) {
            $builder->where(function ($query) use ($q, $bsnEnabled) {
                $query->where('iban', 'LIKE', '%' . $q . '%');
                $query->orWhere('name', 'LIKE', '%' . $q . '%');

                $query->orWhereHas('profile.identity', function ($identityQuery) use ($q, $bsnEnabled) {
                    $identityQuery->where('email', 'LIKE', '%' . $q . '%');

                    if ($bsnEnabled) {
                        $identityQuery->orWhere('bsn', 'LIKE', '%' . $q . '%');
                    }
                });
            });
        }

        return $builder;
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation|ProfileBankAccount
     */
    public static function queryForOrganization(Organization $organization): Builder|Relation|ProfileBankAccount
    {
        return ProfileBankAccountQuery::whereHasPayoutBankAccountForOrganization(
            ProfileBankAccount::query(),
            $organization
        );
    }
}
