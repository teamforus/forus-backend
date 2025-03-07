<?php

namespace App\Searches\Sponsor;

use App\Scopes\Builders\EmailLogQuery;
use App\Searches\BaseSearch;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Database\Eloquent\Builder;

class EmailLogSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder $builder
     */
    public function __construct(array $filters, Builder $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return EmailLog|Builder
     */
    public function query(): ?Builder
    {
        /** @var EmailLog|Builder $builder */
        $builder = parent::query();

        if ($this->getFilter('q')) {
            EmailLogQuery::whereQueryFilter($builder, $this->getFilter('q'));
        }

        return $builder;
    }
}
