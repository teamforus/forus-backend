<?php

namespace App\Searches\Sponsor\PayoutBankAccounts;

use App\Models\FundRequest;
use App\Models\Organization;
use App\Scopes\Builders\FundRequestQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundRequestPayoutBankAccountSearch extends BasePayoutBankAccountSearch
{
    /**
     * @return Builder|Relation|FundRequest
     */
    public function query(): Builder|Relation|FundRequest
    {
        /** @var Builder|Relation|FundRequest $builder */
        $builder = parent::query();

        $q = $this->getFilter('q');
        $bsnEnabled = $this->getFilter('bsn_enabled');
        $identityId = $this->getFilter('identity_id');

        if ($identityId) {
            $builder->where('identity_id', $identityId);
        }

        if ($q) {
            $builder->where(function ($builder) use ($q, $bsnEnabled) {
                $builder->whereHas('identity', function ($identityBuilder) use ($q, $bsnEnabled) {
                    $identityBuilder->where('email', 'LIKE', '%' . $q . '%');

                    if ($bsnEnabled) {
                        $identityBuilder->orWhere('bsn', 'LIKE', '%' . $q . '%');
                    }
                });

                $builder->orWhereHas('fund', function ($fundBuilder) use ($q) {
                    $fundBuilder->where('name', 'LIKE', '%' . $q . '%');
                });

                $builder->orWhereHas('vouchers', function ($voucherBuilder) use ($q) {
                    $voucherBuilder->where('number', 'LIKE', '%' . $q . '%');

                    if (is_numeric($q)) {
                        $voucherBuilder->orWhereKey($q);
                    }
                });
            });
        }

        return $builder;
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation|FundRequest
     */
    public static function queryForOrganization(Organization $organization): Builder|Relation|FundRequest
    {
        return FundRequestQuery::whereHasPayoutBankAccountRecordsForOrganization(FundRequest::query(), $organization);
    }
}
