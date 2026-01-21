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

        if ($this->getFilter('q')) {
            $builder->where(function ($query) {
                $query->where('iban', 'LIKE', '%' . $this->getFilter('q') . '%');
                $query->orWhere('name', 'LIKE', '%' . $this->getFilter('q') . '%');

                $query->orWhereHas('profile.identity', function ($q) {
                    $q->where('email', 'LIKE', '%' . $this->getFilter('q') . '%');

                    if ($this->getFilter('bsn_enabled')) {
                        $q->orWhere('bsn', 'LIKE', '%' . $this->getFilter('q') . '%');
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
