<?php

namespace App\Searches;

use App\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecordSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|Record $builder
     * @param bool $hideSystemRecords
     */
    public function __construct(
        array $filters,
        Builder|Relation|Record $builder,
        protected bool $hideSystemRecords = false,
    ) {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|Record
     */
    public function query(): Builder|Relation|Record
    {
        /** @var Builder|Relation|Record $builder */
        $builder = parent::query();

        if ($this->hasFilter('type')) {
            $builder->whereRelation('record_type', 'key', '=', $this->getFilter('type'));
        }

        if ($this->hideSystemRecords) {
            $builder->whereRelation('record_type', 'system', '=', false);
        }

        if ($this->hasFilter('record_category_id')) {
            $builder->where('record_category_id', '=', $this->getFilter('record_category_id'));
        }

        return $builder->orderBy('order');
    }
}
