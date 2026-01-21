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

        if ($this->getFilter('q')) {
            $builder->where(function ($builder) {
                $builder->whereHas('identity', function ($identityBuilder) {
                    $identityBuilder->where('email', 'LIKE', '%' . $this->getFilter('q') . '%');

                    if ($this->getFilter('bsn_enabled')) {
                        $identityBuilder->orWhere('bsn', 'LIKE', '%' . $this->getFilter('q') . '%');
                    }
                });

                $builder->orWhereHas('fund', function ($fundBuilder) {
                    $fundBuilder->where('name', 'LIKE', '%' . $this->getFilter('q') . '%');
                });

                $builder->orWhereHas('vouchers', function ($voucherBuilder) {
                    $voucherBuilder->where('number', 'LIKE', '%' . $this->getFilter('q') . '%');

                    if (is_numeric($this->getFilter('q'))) {
                        $voucherBuilder->orWhereKey($this->getFilter('q'));
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
