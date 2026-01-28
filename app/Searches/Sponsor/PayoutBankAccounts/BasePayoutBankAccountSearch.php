<?php

namespace App\Searches\Sponsor\PayoutBankAccounts;

use App\Models\Organization;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class BasePayoutBankAccountSearch extends BaseSearch
{
    public function __construct(Organization $organization, array $filters)
    {
        parent::__construct(
            [...$filters, 'bsn_enabled' => $organization->bsn_enabled],
            static::queryForOrganization($organization)
        );
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation|Model
     */
    abstract public static function queryForOrganization(Organization $organization): Builder|Relation|Model;
}
