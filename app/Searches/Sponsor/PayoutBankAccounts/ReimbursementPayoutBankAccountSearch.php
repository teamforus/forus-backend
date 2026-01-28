<?php

namespace App\Searches\Sponsor\PayoutBankAccounts;

use App\Models\Organization;
use App\Models\Reimbursement;
use App\Scopes\Builders\ReimbursementQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReimbursementPayoutBankAccountSearch extends BasePayoutBankAccountSearch
{
    /**
     * @return Builder|Relation
     */
    public function query(): Builder|Relation
    {
        /** @var Builder|Relation|Reimbursement $builder */
        $builder = parent::query();

        $q = $this->getFilter('q');
        $bsnEnabled = $this->getFilter('bsn_enabled');
        $identityId = $this->getFilter('identity_id');

        if ($identityId) {
            $builder->whereRelation('voucher', 'identity_id', $identityId);
        }

        if ($q) {
            $builder->where(function ($query) use ($q, $bsnEnabled) {
                $query
                    ->where('iban', 'LIKE', '%' . $q . '%')
                    ->orWhere('iban_name', 'LIKE', '%' . $q . '%')
                    ->orWhereHas('voucher.identity', function ($identityQuery) use ($q, $bsnEnabled) {
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
     * @return Builder|Relation|Reimbursement
     */
    public static function queryForOrganization(Organization $organization): Builder|Relation|Reimbursement
    {
        return ReimbursementQuery::whereHasPayoutBankAccountForOrganization(
            Reimbursement::query(),
            $organization
        );
    }
}
