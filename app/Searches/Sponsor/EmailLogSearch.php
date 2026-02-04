<?php

namespace App\Searches\Sponsor;

use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\EmailLogQuery;
use App\Searches\BaseSearch;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class EmailLogSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|EmailLog $builder
     * @param Organization $organization
     */
    public function __construct(
        array $filters,
        Builder|Relation|EmailLog $builder,
        protected Organization $organization,
    ) {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|EmailLog
     */
    public function query(): Builder|Relation|EmailLog
    {
        /** @var Builder|Relation|EmailLog $builder */
        $builder = parent::query();
        $identityId = $this->getFilter('identity_id');
        $fundRequestId = $this->getFilter('fund_request_id');

        if (!$identityId && !$fundRequestId) {
            abort(403, 'Access denied: missing identity or fund request identifier.');
        }

        if ($this->getFilter('q')) {
            EmailLogQuery::whereQueryFilter($builder, $this->getFilter('q'));
        }

        if ($identityId) {
            $builder = EmailLogQuery::whereIdentity($builder, Identity::find($identityId), $this->organization);
        }

        if ($fundRequestId) {
            $builder = EmailLogQuery::whereFundRequest($builder, FundRequest::find($fundRequestId), $this->organization);
        }

        return $builder->latest();
    }
}
