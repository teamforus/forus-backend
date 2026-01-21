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

        if ($this->getFilter('q')) {
            $builder->where(function ($query) {
                $query->where('iban', 'LIKE', '%' . $this->getFilter('q') . '%')
                    ->orWhere('iban_name', 'LIKE', '%' . $this->getFilter('q') . '%')
                    ->orWhereHas('voucher.identity', function ($q) {
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
