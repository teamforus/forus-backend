<?php

namespace App\Searches\Sponsor\PayoutBankAccounts;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class PayoutTransactionPayoutBankAccountSearch extends BasePayoutBankAccountSearch
{
    /**
     * @return Builder|Relation
     */
    public function query(): Builder|Relation
    {
        /** @var Builder|Relation|VoucherTransaction $builder */
        $builder = parent::query();

        if ($this->getFilter('q')) {
            $builder->where(function ($query) {
                $query->where('target_iban', 'LIKE', '%' . $this->getFilter('q') . '%')
                    ->orWhere('target_name', 'LIKE', '%' . $this->getFilter('q') . '%')
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
     * @return Builder|Relation|VoucherTransaction
     */
    public static function queryForOrganization(Organization $organization): Builder|Relation|VoucherTransaction
    {
        return VoucherTransactionQuery::whereHasPayoutBankAccountForOrganization(
            VoucherTransaction::query(),
            $organization
        );
    }
}
