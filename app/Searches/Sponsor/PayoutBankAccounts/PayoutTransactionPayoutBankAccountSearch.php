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

        $q = $this->getFilter('q');
        $bsnEnabled = $this->getFilter('bsn_enabled');
        $identityId = $this->getFilter('identity_id');

        if ($identityId) {
            $builder->whereHas('voucher', fn ($query) => $query->where('identity_id', $identityId));
        }

        if ($q) {
            $builder->where(function ($query) use ($q, $bsnEnabled) {
                $query->where('target_iban', 'LIKE', '%' . $q . '%')
                    ->orWhere('target_name', 'LIKE', '%' . $q . '%')
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
